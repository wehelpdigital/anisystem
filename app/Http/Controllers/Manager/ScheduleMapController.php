<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleMapLocation;
use App\Events\ScheduleMapPushed;
use App\Models\AsScheduleNote;
use App\Models\ScheduleMapObject;
use App\Models\ScheduleMapSave;
use App\Support\MapAccess;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The map: shapes drawn over real ground, and the saved maps they become.
 *
 * Two canvases share this controller (2026-09-30). `?scheduleId=N` is a
 * season's TEAM canvas, the one the Collab Room draws on together: shapes
 * persist and broadcast, live positions only broadcast. No scheduleId (or 0)
 * is the caller's OWN canvas, behind the global Maps page and a lot's map.
 *
 * Saved maps are never a season's any more: they are their owner's
 * (scheduleId 0), linked to the seasons that use them. App\Support\MapAccess
 * decides who may open or change one; every save-touching door asks it.
 */
class ScheduleMapController extends BaseScheduleController
{
    /**
     * The lettering a label may be written in — keys, not font stacks.
     *
     * The client owns the stacks, because which families a phone actually has
     * is a client question and the answer keeps changing. Four of them, all
     * already installed everywhere: a sans, a serif, a condensed and a
     * monospace. Nothing here downloads a font — the map is read in a field,
     * on whatever signal the field has.
     */
    private const FONTS = 'sans,serif,cond,mono';

    /**
     * Maps, in Global and Quick Tools: every map of your own (and, for a
     * worker holding the Maps pen, the farm's), on your own canvas. It was a
     * module of each season until 2026-09-30.
     */
    public function page(Request $request)
    {
        return view('sm.maps', [
            'schedule' => null,
            'saves' => MapAccess::rows(MapAccess::shelf()->orderByDesc('id')->limit(200)->get()),
            // Whether your canvas holds anything worth going back to.
            'liveCount' => $this->canvasQuery(null)->count(),
            'attachLot' => null,
        ]);
    }

    /**
     * The season Maps module's old address. A season no longer has maps of
     * its own; a link to one still lands -- on the map it named, or on a
     * lot's own map when it came from a lot.
     */
    public function legacyPage(Request $request)
    {
        $sid = (int) $request->query('id');
        if ($sid > 0 && (int) $request->query('lot') > 0) {
            return redirect()->route('sm.lots.map', ['id' => $sid, 'lot' => (int) $request->query('lot')]);
        }

        return redirect()->route('maps.page', array_filter(['save' => (int) $request->query('save')]));
    }

    /**
     * Which of your own canvases a request draws on when it is not a
     * season's: 0 is the Maps page's, and -lotId is a lot's map, so opening
     * a lot never clears the map you left unsaved on the Maps page. Rows are
     * always fenced to your own userId as well.
     */
    private int $ownKey = 0;

    /**
     * Which canvas a request draws on: a season's team canvas (the Collab
     * Room) when it names one, else one of the caller's own. Returns the
     * season, or null for your own canvas.
     */
    private function canvas(Request $request): ?\App\Models\AsCroppingSchedule
    {
        $sid = (int) $request->query('scheduleId');
        if ($sid <= 0) {
            $this->ownKey = $sid;

            return null;
        }
        $schedule = $this->schedule($sid);
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            abort(response()->json(['success' => false, 'message' => 'You are not part of this schedule team.'], 403));
        }

        return $schedule;
    }

    /** The shapes on a canvas: the season's, or the caller's own. */
    private function canvasQuery(?\App\Models\AsCroppingSchedule $schedule)
    {
        return $schedule
            ? ScheduleMapObject::active()->where('scheduleId', $schedule->id)
            : ScheduleMapObject::active()->where('scheduleId', $this->ownKey)->where('userId', (int) Auth::id());
    }

    /**
     * Changing a team canvas is an edit of the farm's record; your own canvas
     * is yours, whatever a boss's grant says.
     */
    private function assertCanDraw(?\App\Models\AsCroppingSchedule $schedule): void
    {
        if ($schedule) {
            $this->assertCanEdit();
        }
    }

    public function objects(Request $request)
    {
        $schedule = $this->canvas($request);

        $rows = $this->canvasQuery($schedule)
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
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();
        // Membership was the whole of this test, so a VIEW-ONLY worker could
        // put shapes on the team's map by calling this directly — the one
        // promise "view-only access" makes is that nothing they do is
        // written down. Drawing is an edit like reshaping and removing,
        // which have asked this all along.
        $this->assertCanDraw($schedule);

        $validator = Validator::make($request->all(), [
            'kind' => 'required|in:pen,line,path,rect,area,text,arrow,pin',
            'color' => 'nullable|string|max:16',
            // 20 was the pen's ceiling. A label's type size lives in the same
            // column and wants to go bigger than any line ever would.
            'width' => 'nullable|integer|min:1|max:64',
            'points' => 'required|array|min:1|max:2000',
            'points.*' => 'array|size:2',
            'label' => 'nullable|string|max:500',
            'font' => 'nullable|in:' . self::FONTS,
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = ScheduleMapObject::create([
            'scheduleId' => $schedule?->id ?? $this->ownKey,
            'userId' => $meId,
            'kind' => $request->input('kind'),
            'color' => $request->input('color'),
            'width' => (int) $request->input('width', 3),
            'font' => $request->input('font'),
            'points' => json_encode($request->input('points')),
            'label' => $request->input('label'),
            'deleteStatus' => 1,
        ]);

        $this->emit($schedule, ['action' => 'add', 'object' => $object->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->shaped()]]);
    }

    /** Move or reshape an existing object; the team sees it land live. */
    public function update(Request $request)
    {
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();

        // Reshaping someone's lot boundary is an edit to the farm's own map.
        // This door resolves with the raw resolver, which carries no write
        // check of its own — so being on the team was the whole of the test,
        // and a view-only member could drag a field to a different shape.
        $this->assertCanDraw($schedule);

        // Everything but the id is optional, and only what was sent is
        // written. This door used to move geometry and nothing else, so a
        // label's words, its lettering and its size each needed a door of
        // their own — three more endpoints saying the same thing about the
        // same row. They come through here instead.
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'points' => 'sometimes|array|min:1|max:2000',
            'points.*' => 'array|size:2',
            'label' => 'sometimes|nullable|string|max:500',
            'font' => 'sometimes|nullable|in:' . self::FONTS,
            'width' => 'sometimes|integer|min:1|max:64',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = $this->canvasQuery($schedule)->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape no longer exists.', 404);
        }

        $patch = [];
        if ($request->has('points')) {
            $patch['points'] = json_encode($request->input('points'));
        }
        if ($request->has('label')) {
            $patch['label'] = $request->input('label');
        }
        if ($request->has('font')) {
            $patch['font'] = $request->input('font');
        }
        if ($request->has('width')) {
            $patch['width'] = (int) $request->input('width');
        }
        if (empty($patch)) {
            return $this->jsonFail('Nothing to change on that shape.', 422);
        }

        $object->update($patch);
        $this->emit($schedule, ['action' => 'update', 'object' => $object->fresh()->shaped(), 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'data' => ['object' => $object->fresh()->shaped()]]);
    }

    public function remove(Request $request)
    {
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();

        // Same line clear() and update() draw: membership lets you draw,
        // but taking a shape OFF the team's map — even one at a time — is an
        // edit of the farm's record. The button is already gone for a
        // view-only worker; this is the lock behind that door.
        $this->assertCanDraw($schedule);

        $object = $this->canvasQuery($schedule)->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape is already gone.', 404);
        }

        $object->update(['deleteStatus' => 0]);
        $this->emit($schedule, ['action' => 'remove', 'id' => (int) $object->id, 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => 'Removed.']);
    }

    public function clear(Request $request)
    {
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();
        // Membership lets you draw; wiping the whole team's canvas is an edit
        // of the schedule's record, and a view-only worker holds no such right.
        // The button is hidden for them too — this is the lock behind the door.
        if ($schedule && ! \App\Support\WorkerContext::canEdit()) {
            return $this->jsonFail('Only someone with edit access can clear the team map.', 403);
        }

        $this->canvasQuery($schedule)->update(['deleteStatus' => 0]);
        $this->emit($schedule, ['action' => 'clear', 'actorUserId' => $meId]);

        return response()->json(['success' => true, 'message' => $schedule ? 'Map cleared for the team.' : 'Map cleared.']);
    }

    /**
     * Drawing-in-progress relay: the half-drawn shape under a member's finger,
     * so the room watches it grow instead of having it pop in finished.
     * Broadcast-only; `done` tells viewers to drop the ghost.
     */
    public function trace(Request $request)
    {
        $schedule = $this->canvas($request);
        $me = Auth::user();
        // Your own canvas has nobody watching it.
        if (! $schedule) {
            return response()->json(['success' => true]);
        }

        $validator = Validator::make($request->all(), [
            'done' => 'nullable|boolean',
            'kind' => 'nullable|in:pen,line,path,rect,area,arrow,pin',
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
        $this->canvas($request);

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

    /**
     * The saved maps a canvas may open. On your own canvas: your shelf (see
     * MapAccess::shelf). On a season's team canvas: the maps that season
     * uses, and your own.
     */
    public function saves(Request $request)
    {
        $schedule = $this->canvas($request);
        $rows = ($schedule ? MapAccess::choices($schedule->id) : MapAccess::shelf())
            ->orderByDesc('id')->limit(200)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'saves' => MapAccess::rows($rows),
                // How many shapes the canvas holds right now, so the shelf
                // can tell whether "The canvas" is worth a card.
                'liveCount' => $this->canvasQuery($schedule)->count(),
            ],
        ]);
    }

    /**
     * The search box: places by name (App\Support\PlaceSearch). Asked once
     * per Enter, never per keystroke; the map flies to the one picked.
     */
    public function places(Request $request)
    {
        $words = mb_substr(trim((string) $request->query('q', '')), 0, 120);

        return $this->jsonOk('ok', ['data' => ['places' => \App\Support\PlaceSearch::find($words)]]);
    }

    /**
     * A picture for the shelf, for EVERY saved map: the filed one when its
     * file still answers, else one redrawn from the save's own shapes — they
     * live in the row, so they survive the wiped disks that ate the files.
     */
    public function thumb(Request $request)
    {
        $save = MapAccess::viewable($request->query('id'));
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        // The filed picture first, while its file still answers: some did
        // not survive the moves between hosts. Those are redrawn from the
        // shapes below, and the note gets a fresh picture so the Gallery and
        // every chip have it again.
        $note = $save->noteId ? AsScheduleNote::active()->find($save->noteId) : null;
        $path = MapAccess::picturePath($note);
        if ($path !== null && \App\Support\MediaStore::exists($path)) {
            return redirect()->away(\App\Support\MediaStore::url($path));
        }

        $objects = collect(json_decode((string) $save->objects, true) ?: [])
            ->filter(fn ($o) => is_array($o['points'] ?? null) && $o['points'])
            ->map(fn ($o) => [
                'kind' => $o['kind'] ?? 'pen',
                'color' => $o['color'] ?? null,
                'width' => (int) ($o['width'] ?? 3),
                'font' => $o['font'] ?? null,
                'points' => $o['points'],
                'label' => $o['label'] ?? null,
            ])->values()->all();

        // Best-effort cache: the key carries updated_at, so a re-saved map
        // draws itself afresh, and a cache that cannot answer never blocks.
        $cacheKey = 'mapthumb:' . $save->id . ':' . ($save->updated_at?->timestamp ?? 0);
        $binary = null;
        try {
            $binary = \Illuminate\Support\Facades\Cache::get($cacheKey);
        } catch (\Throwable $e) {
            // cache misconfigured — draw it every time
        }

        if (! is_string($binary) || $binary === '') {
            // A card-sized render, not the full save picture — a shelf of
            // megabyte thumbnails is what made the module feel slow.
            // The satellite render first; when the map service is short of a
            // key, a quota or a connection, the plan is drawn from its own
            // shapes (App\Support\MapThumb) -- a saved map always has a face.
            $url = $this->staticMapUrl($objects, null, null, null, 'hybrid', 400, 1);
            if ($url !== null) {
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout(12)->get($url);
                    if ($res->ok() && str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                        $binary = $res->body();
                    }
                } catch (\Throwable $e) {
                    $binary = null;
                }
            }
            if (! is_string($binary) || $binary === '') {
                $binary = \App\Support\MapThumb::png($objects, 400, 300);
            }
            if (! is_string($binary) || $binary === '') {
                return $this->jsonFail('This map has no shapes to draw yet.', 404);
            }
            try {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $binary, now()->addDays(7));
            } catch (\Throwable $e) {
                // fine — the browser's own cache still helps
            }
            if ($note && $path !== null) {
                $this->mendMapPicture($note, $path, $objects);
            }
        }

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * A map's filed picture was lost: take a fresh one (Static Maps, with the
     * shapes drawn on) and put it where the old one was on the note. Best
     * effort -- the shelf already has its redrawn card either way.
     */
    private function mendMapPicture(AsScheduleNote $note, string $lost, array $objects): void
    {
        try {
            $url = $this->staticMapUrl($objects, null, null, null, 'hybrid');
            $res = $url ? \Illuminate\Support\Facades\Http::timeout(20)->get($url) : null;
            if (! $res || ! $res->ok() || ! str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                return;
            }
            $scope = (int) $note->croppingScheduleId ?: 'u' . (int) $note->userId;
            $fresh = \App\Support\MediaStore::putBinary($res->body(), 'maps', 'png', $scope, 'map-');
            if (! $fresh) {
                return;
            }
            $media = is_array($note->media) ? $note->media : [];
            foreach ($media as &$m) {
                if (($m['path'] ?? null) === $lost) {
                    $m['path'] = $fresh;
                    $m['type'] = 'map';
                }
            }
            unset($m);
            $note->media = array_values($media);
            $note->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Could not mend a map picture: ' . $e->getMessage());
        }
    }

    /**
     * Save the whole map. mode=plain keeps a reopenable snapshot and nothing
     * else; mode=map keeps it AND files its picture in a note; mode=image
     * files only the picture. The picture comes
     * from the canvas the client composed, else the Static Maps API with
     * every shape drawn on it — the live WebGL map cannot be screenshotted.
     *
     * A map is always its owner's (2026-09-30): a new one belongs to whoever
     * saves it, and its picture note goes to their Global Notes. Saved from a
     * season's team canvas, it is also linked to that season. Writing into an
     * existing map needs MapAccess::canEdit -- its owner, or a worker with
     * the Maps pen on a season that uses it.
     */
    public function saveMap(Request $request)
    {
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();
        // Filing from a team canvas writes into the season's records -- the
        // note right, same line the whiteboard's save draws. Your own canvas
        // files into your own notes.
        if ($schedule && ! \App\Support\WorkerContext::canAddNotes()) {
            return $this->jsonFail('You are not allowed to save to this schedule.', 403);
        }
        // The tier's map shelf: how many saved maps this member may keep.
        $mapCap = \App\Support\Tier::limit('mapsTotal');
        if ($mapCap !== null && ! $request->input('saveId')
            && MapAccess::owned($meId)->count() >= $mapCap) {
            \App\Support\Tier::denyFor('mapsTotal', 'Your plan keeps up to ' . $mapCap . ' saved maps. Delete one, or move up to {plan} for more.');
        }

        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:plain,map,image',
            // Which saved map to write into. Absent means a new one.
            'saveId' => 'nullable|integer',
            // An autosave rather than someone pressing Save: it writes into
            // the map it was given and never mints anything of its own.
            'quiet' => 'nullable|boolean',
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

        $objects = $this->canvasQuery($schedule)
            ->orderBy('id')
            ->limit(2000)
            ->get()
            ->map(fn ($o) => $o->shaped())
            ->all();
        if (empty($objects)) {
            return $this->jsonFail('The map has no shapes to save yet.', 422);
        }

        $mode = $request->input('mode');
        $quiet = $request->boolean('quiet');
        // Both write a map file; only "map" also files a picture note.
        $writesMap = $mode !== 'image';
        // An autosave, and a plain "Save map", keep the note a map already has
        // current but never file a new one: a map saved as just a map stays
        // out of the notebook through every edit that follows.
        $noNewNote = $quiet || $mode === 'plain';
        $title = trim((string) $request->input('title')) ?: 'Map';
        $source = $request->input('source') === 'team' ? 'team' : 'solo';
        $description = trim((string) $request->input('description'));

        // Which saved map is being written into. Resolved up here rather than
        // at the end, because an autosave has to know whether it already owns
        // a note before it goes and makes one.
        $existing = null;
        if ($writesMap && $request->filled('saveId')) {
            $existing = ScheduleMapSave::active()->find((int) $request->input('saveId'));
            if ($existing && ! MapAccess::canEdit($existing)) {
                return $this->jsonFail('You can open this map but not change it. Save it as a new map of your own instead.', 403);
            }
        }

        // An autosave with nowhere to write is a bug on the client, not an
        // invitation to file a new map behind the user's back.
        if ($quiet && ! $existing) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        // Whose map (and whose note) this is: the file's owner when writing
        // into one, else whoever is saving it.
        $ownerId = $existing ? (int) $existing->userId : $meId;
        // Where the picture is kept: a map's note is its owner's own; a
        // picture-only save from a team canvas is a note in that season.
        $bucket = ($mode === 'image' && $schedule) ? $schedule->id : 0;

        // Best-effort picture; the reopenable snapshot never depends on it.
        $media = [];
        // The note this save writes back into, when it keeps one (see above).
        $keptNote = ($noNewNote && $existing?->noteId)
            ? AsScheduleNote::active()->find($existing->noteId)
            : null;
        // A picture only goes somewhere as a note. With none to file or to
        // keep current it would be a file nothing points at.
        $wantsPicture = ! $noNewNote || $keptNote !== null;

        // "Save as image note" means exactly that: a picture to look at, which
        // notes render as a picture. Only the reopenable snapshot is typed as a
        // map, because only that one has a map to go back to. The filenames
        // differ for the same reason — notes recognise older map pictures by
        // the "map-" name, and an image save must not be caught by it.
        $mediaType = $mode === 'map' ? 'map' : 'image';
        $namePrefix = $mode === 'map' ? 'map-' : 'mapimg-';

        // Preferred: the canvas the client composed, which carries the points
        // and measurement labels. Static Maps can draw the shapes but cannot
        // write their sizes, so that path is the fallback, not the goal.
        $binary = $wantsPicture ? $this->decodeDataUrlImage((string) $request->input('image')) : null;
        if ($binary !== null) {
            $path = \App\Support\MediaStore::putBinary($binary, 'maps', 'png', $bucket ?: 'u' . $ownerId, $namePrefix);
            if ($path !== null) {
                $media[] = ['type' => $mediaType, 'path' => $path, 'poster' => null];
            }
        }

        $url = ($media || ! $wantsPicture) ? null : $this->staticMapUrl(
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
                    $path = \App\Support\MediaStore::putBinary($img->body(), 'maps', 'png', $bucket ?: 'u' . $ownerId, $namePrefix);
                    if ($path !== null) {
                        $media[] = ['type' => $mediaType, 'path' => $path, 'poster' => null];
                    }
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
                . 'Saved map — tap View map to open it.');
        }
        // A map that saves itself as it is edited writes back into the note it
        // already has. Minting one per autosave would bury an afternoon's
        // notebook under forty pictures of the same field.
        $note = $keptNote;
        if ($note) {
            $was = is_array($note->media) ? $note->media : [];
            // Only the map's own picture is replaced. Anything a person hung on
            // that note afterwards is theirs and stays where they put it.
            $stale = array_filter($was, fn ($m) => ($m['type'] ?? '') === 'map');
            $theirs = array_values(array_filter($was, fn ($m) => ($m['type'] ?? '') !== 'map'));
            $note->update([
                'title' => mb_substr($title, 0, 180),
                // Keep the picture already filed when this round could not take
                // a fresh one — a note with no media renders as an empty card.
                'media' => $media ? array_merge($media, $theirs) : $was,
            ]);
            // And take the replaced one off the disk. A map saving itself every
            // fifteen seconds for an afternoon is otherwise a few hundred PNGs
            // of the same field that nothing will ever look at again.
            if ($media) {
                foreach ($stale as $old) {
                    if (is_string($old['path'] ?? null)) {
                        \App\Support\MediaStore::delete($old['path']);
                    }
                }
            }
        } elseif (! $noNewNote) {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => $bucket,
                'userId' => $bucket ? $meId : $ownerId,
                'title' => mb_substr($title, 0, 180),
                'body' => $bodyText !== null
                    ? \App\Support\HtmlSanitizer::rich('<p>' . nl2br(e($bodyText)) . '</p>')
                    : null,
                'media' => $media,
                'deleteStatus' => 1,
            ]);
        }

        $saveId = null;
        if ($writesMap) {
            // Whatever is not written here is not in the file, and so is gone
            // the next time the map is opened — which is how a saved map came
            // back with every label reset to the default face.
            $shapes = json_encode(array_map(fn ($o) => [
                'kind' => $o['kind'], 'color' => $o['color'], 'width' => $o['width'],
                'font' => $o['font'] ?? null,
                'points' => $o['points'], 'label' => $o['label'],
            ], $objects));

            // Writing back into the file this map was opened from: the same
            // record keeps its place in the list and its note keeps its
            // history, rather than ending up with three copies of one plan
            // and no way to tell which is current.
            if ($existing) {
                // A save that filed no note leaves the map's own note where it is.
                $existing->update(array_filter([
                    'title' => mb_substr($title, 0, 180),
                    'objects' => $shapes,
                    'noteId' => $note?->id,
                ], fn ($v) => $v !== null));
                $saveId = $existing->id;
            } else {
                $saveId = ScheduleMapSave::create([
                    'scheduleId' => MapAccess::GLOBAL,
                    'originScheduleId' => $schedule?->id,
                    'userId' => $meId,
                    'title' => mb_substr($title, 0, 180),
                    'source' => $source,
                    'objects' => $shapes,
                    'noteId' => $note?->id,
                    'deleteStatus' => 1,
                ])->id;
            }

            if ($schedule) {
                // The season this was drawn in uses it, so its team can open it.
                MapAccess::link((int) $saveId, $schedule->id);
                if ($request->has('tags')) {
                    \App\Support\ScheduleTags::sync($schedule, 'map', (int) $saveId, $request->input('tags', []));
                }
            }

            // Everyone in the room is looking at these same shapes. Tell them
            // WHICH file they now belong to, so the next person to nudge one
            // writes back into it instead of forking a second copy.
            $this->emit($schedule, [
                'action' => 'saved',
                'saveId' => (int) $saveId,
                'title' => mb_substr($title, 0, 180),
                'actorUserId' => $meId,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => ['saveId' => $saveId, 'title' => mb_substr($title, 0, 180)],
            'message' => $quiet
                ? 'Saved.'
                : ($writesMap
                ? (($saveId && $request->filled('saveId'))
                    ? 'Saved over “' . mb_substr($title, 0, 60) . '”.'
                    : 'Map saved to your Maps — reopen it any time from Global and Quick Tools'
                    . ($mode === 'plain'
                        ? '.'
                        : (empty($media) ? ' (no picture: Static Maps API unavailable).' : ', and its picture is in your Notes.')))
                : 'Saved to Notes as an image note.'),
        ]);
    }

    /**
     * Rename a saved map and reword what it was for, without opening the
     * stage. The note the save filed keeps saying the same thing as the
     * shelf: same title, and the description ahead of the boilerplate line
     * the save itself wrote. Tags are a season's, so they are only tied when
     * this comes from inside a season (?scheduleId=).
     */
    public function saveMeta(Request $request)
    {
        $schedule = $this->canvas($request);

        $validator = Validator::make($request->all(), [
            'saveId' => 'required|integer',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
            'tags' => 'nullable',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $save = MapAccess::editable($request->input('saveId'));
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists, or is not yours to change.', 404);
        }

        $title = trim((string) $request->input('title')) ?: 'Map';
        $save->update(['title' => mb_substr($title, 0, 180)]);

        if ($save->noteId && ($note = AsScheduleNote::active()->find($save->noteId))) {
            $description = trim((string) $request->input('description'));
            $bodyText = trim(($description !== '' ? $description . "\n\n" : '')
                . 'Saved map — tap View map to open it.');
            $note->update([
                'title' => mb_substr($title, 0, 180),
                'body' => \App\Support\HtmlSanitizer::rich('<p>' . nl2br(e($bodyText)) . '</p>'),
            ]);
        }

        if ($schedule && $request->has('tags')) {
            MapAccess::link((int) $save->id, $schedule->id);
            \App\Support\ScheduleTags::sync($schedule, 'map', (int) $save->id, $request->input('tags', []));
        }

        return $this->jsonOk('Map updated.', ['data' => [
            'id' => (int) $save->id,
            'title' => $save->title,
            'description' => trim((string) $request->input('description')),
        ]]);
    }

    /**
     * Throw a saved map away. Only its owner may; the seasons that used it
     * lose it with it (their lots forget it, its links go).
     */
    public function deleteSave(Request $request)
    {
        $save = ScheduleMapSave::active()->find((int) $request->input('id'));
        if (! $save || ! MapAccess::isOwner($save)) {
            return $this->jsonFail('That saved map no longer exists, or is not yours to delete.', 404);
        }
        $save->update(['deleteStatus' => 0]);
        \App\Models\AsScheduleLot::where('mapSaveId', $save->id)->update(['mapSaveId' => null]);
        \Illuminate\Support\Facades\DB::table('as_schedule_map_links')->where('saveId', $save->id)->delete();

        return $this->jsonOk('Map deleted.');
    }

    /** Put a saved map on a canvas, replacing what is there. */
    public function loadSave(Request $request)
    {
        $schedule = $this->canvas($request);
        $meId = (int) Auth::id();

        // Restoring a save clears every shape on the team's map first. That is
        // a destructive edit to the farm's own map, not a thing that happens
        // only inside the room, so it asks the editing question.
        $this->assertCanDraw($schedule);

        $save = MapAccess::viewable($request->input('id'));
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        $objects = json_decode((string) $save->objects, true) ?: [];
        $this->canvasQuery($schedule)->update(['deleteStatus' => 0]);
        foreach (array_slice($objects, 0, 2000) as $o) {
            if (! is_array($o['points'] ?? null) || empty($o['points'])) {
                continue;
            }
            ScheduleMapObject::create([
                'scheduleId' => $schedule?->id ?? $this->ownKey,
                'userId' => $meId,
                'kind' => $o['kind'] ?? 'pen',
                'color' => $o['color'] ?? null,
                'width' => (int) ($o['width'] ?? 3),
                // Saves written before lettering existed have no font, and
                // null is exactly right for them: the client reads that as
                // "an old label" and draws it the way it always drew.
                'font' => $o['font'] ?? null,
                'points' => json_encode($o['points']),
                'label' => $o['label'] ?? null,
                'deleteStatus' => 1,
            ]);
        }

        // One event; every client refetches rather than replaying a giant diff.
        // It names the save, too: a client that does not know which file is on
        // screen cannot write its own edits back into it.
        $this->emit($schedule, [
            'action' => 'reload',
            'saveId' => (int) $save->id,
            'title' => (string) $save->title,
            'actorUserId' => $meId,
        ]);

        return response()->json([
            'success' => true,
            'message' => $schedule ? 'Map loaded for the team.' : 'Map opened.',
            // Whether the opener may write back into it; a view-only opener's
            // edits stay on their canvas until they save a map of their own.
            'data' => ['canEdit' => MapAccess::canEdit($save), 'mine' => MapAccess::isOwner($save), 'title' => (string) $save->title],
        ]);
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
    private function staticMapUrl(array $objects, $lat, $lng, $zoom, ?string $maptype, int $size = 640, int $scale = 2): ?string
    {
        $key = (string) config('services.google_maps.key');
        if ($key === '') {
            return null;
        }

        $base = 'https://maps.googleapis.com/maps/api/staticmap?size=' . $size . 'x' . $size . '&scale=' . $scale
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

            if ($o['kind'] === 'pin') {
                // Static Maps draws a marker natively, and full size — a
                // one-point polyline would draw nothing at all, which is how
                // a saved picture ends up missing the one thing the map was
                // saved FOR.
                $piece = '&markers=' . rawurlencode('color:0x' . $color . '|'
                    . round($pts[0][0], 6) . ',' . round($pts[0][1], 6));
            } elseif ($o['kind'] === 'text') {
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
        $schedule = $this->canvas($request);
        $me = Auth::user();
        // Nobody else is on your own canvas to be told where you are.
        if (! $schedule) {
            return response()->json(['success' => true]);
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

    /** Tell a season's room; your own canvas has no room to tell. */
    private function emit(?\App\Models\AsCroppingSchedule $schedule, array $payload): void
    {
        if (! $schedule) {
            return;
        }
        try {
            broadcast(new ScheduleMapPushed($schedule->id, $payload));
        } catch (\Throwable $e) {
            // best-effort — the poll fallback reconciles
        }
    }
}
