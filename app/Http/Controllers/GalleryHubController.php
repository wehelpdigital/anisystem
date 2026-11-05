<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Support\MediaStore;
use App\Support\SeasonMedia;
use App\Support\WorkerContext;
use Illuminate\Http\Request;

/**
 * Every picture from every season, in one place.
 *
 * The Gallery has always been per-schedule, which is right when you are inside
 * a season and wrong the moment you are looking for "that photo of the pest,
 * some time last year". Global Notes already gathers the words; this gathers
 * the pictures, and the messenger borrows it so a farmer can attach anything
 * they have ever kept without first remembering which season it belonged to.
 *
 * It also holds the one thing with no season at all: what Import from phone was
 * told to keep here (as_gallery_images with croppingScheduleId 0, albumId 0 and the
 * person's userId, so only they see it, and their boss when it was saved from a worker
 * account: App\Support\FarmShelf). A row's file can be gone while the
 * row stays, and a tile for nothing is not shown: see stillThere().
 */
class GalleryHubController extends Controller
{
    /** How many tiles arrive at once. */
    private const PER_PAGE = 24;

    /** The moment this request stops asking the file store, once it has begun to. */
    private float $checkUntil = 0.0;

    public function index(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $kinds = collect(explode(',', (string) $request->query('kinds', '')))
            ->map(fn ($k) => trim($k))->filter()->all();
        $q = trim((string) $request->query('q', ''));

        $items = $this->gather($kinds, $q);

        // The messenger and the pickers ask for JSON and want the flat list of
        // everything; only the page itself has shelves.
        if ($request->wantsJson() || $request->boolean('json')) {
            $slice = array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

            return response()->json(['success' => true, 'data' => [
                'items' => $slice,
                'hasMore' => count($items) > $page * self::PER_PAGE,
                'nextPage' => $page + 1,
                'total' => count($items),
            ]]);
        }

        /* The same four shelves the season's own Gallery has.
         *
         * This IS that module, asked across every season instead of one, so it
         * wears the same chrome: a grower who has learned where photos live in
         * a season should not have to learn a second place. Only the shelf
         * being looked at is built — the Team box alone is three queries a
         * season, and paying for it to render a number nobody asked for is how
         * a page with forty seasons behind it stops opening. */
        $tab = in_array($request->query('tab'), ['albums', 'videos', 'voice', 'team'], true)
            ? $request->query('tab')
            : 'all';

        $schedules = $this->schedules();
        $stills = array_values(array_filter($items, fn ($m) => $m['type'] !== 'video'));
        $clips = array_values(array_filter($items, fn ($m) => $m['type'] === 'video'));
        // Every recording kept anywhere — quick voice notes, spoken notes on
        // a season — one shelf, each tile pointing at the one stored file.
        $voices = array_values(array_filter($items, fn ($m) => ($m['kind'] ?? '') === 'audio'));

        $shelf = match ($tab) {
            'albums' => $this->albums($schedules, $q),
            'team' => $this->teamBox($schedules, $q),
            'videos' => $clips,
            'voice' => $voices,
            default => $stills,
        };

        $paged = $tab === 'albums' ? $shelf : array_slice($shelf, 0, self::PER_PAGE);

        $importSeasons = $this->importSeasons($schedules);
        // Clips may go to the farm's own plan on the Global Gallery, or to a season that takes them.
        $importVideo = \App\Support\Tier::farmCan('videoRecording') || $this->importTakesVideo($importSeasons);

        return view('sm.gallery-hub', [
            'tab' => $tab,
            'items' => $paged,
            'albums' => $tab === 'albums' ? $shelf : [],
            'team' => $tab === 'team' ? $shelf : [],
            'hasMore' => $tab !== 'albums' && count($shelf) > self::PER_PAGE,
            'total' => count($shelf),
            'counts' => [
                'all' => count($stills),
                'videos' => count($clips),
                'voice' => count($voices),
                // Counted only when its own shelf is open; the button shows a
                // dash otherwise rather than a number bought at that price.
                'albums' => $tab === 'albums' ? count($shelf) : null,
                'team' => $tab === 'team' ? count($shelf) : null,
            ],
            'q' => $q,
            'importSeasons' => $importSeasons,
            'importVideo' => $importVideo,
        ]);
    }

    /** One file from Import from phone, kept on the Global Gallery with no season. */
    public function import(Request $request)
    {
        $request->validate([
            'image' => 'required_without:clip|image|mimes:jpg,jpeg,png,webp|max:8192',
            'clip' => 'required_without:image|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v|max:2097152',
        ]);

        if ($request->hasFile('clip')) {
            if (! \App\Support\Tier::farmCan('videoRecording')) {
                \App\Support\Tier::farmDenyFor('videoRecording', 'Video recording comes with {plan}. Photos and voice notes stay yours on every plan.');
            }
            try {
                $path = \App\Support\VideoOptimizer::storeCompressed($request->file('clip'), 'gallery')['video'];
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'The video could not be saved.'], 422);
            }
        } else {
            $path = MediaStore::putFile($request->file('image'), 'gallery', \Illuminate\Support\Facades\Auth::id());
        }

        if (! $path) {
            return response()->json(['success' => false, 'message' => 'Nothing could be saved. Please try again.'], 500);
        }

        $row = \App\Models\AsGalleryImage::create([
            'albumId' => 0,
            'croppingScheduleId' => 0,
            'userId' => \Illuminate\Support\Facades\Auth::id(),
            'path' => $path,
            'deleteStatus' => 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Saved to your Global Gallery.', 'id' => (int) $row->id]);
    }

    /** To the Trash, the same road a season's picture takes. Only your own, and only one with no season. */
    public function destroyOwn(Request $request)
    {
        $row = \App\Models\AsGalleryImage::where('croppingScheduleId', 0)
            ->where(\App\Support\FarmShelf::mine())
            ->where('deleteStatus', 1)
            ->find((int) $request->input('id'));
        if (! $row) {
            return response()->json(['success' => false, 'message' => 'That picture is no longer here.'], 404);
        }

        $row->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Moved to the Trash. You can restore it there for 30 days.']);
    }

    /**
     * Is the file behind this row still kept? A row can outlive its file, and a
     * tile that says "File missing" is only noise (2026-11-06, the owner).
     *
     * The answer is remembered, because asking the file store costs a trip. Three
     * seconds of asking per request, newest first: whatever is left is asked on the
     * next visit, and meanwhile the page itself drops a photo that will not load.
     * Not being able to tell is not the same as gone, so that shows the tile.
     */
    private function stillThere(?string $path): bool
    {
        if (blank($path) || MediaStore::isSiteAsset($path)) {
            return true;
        }

        $key = 'gallery:there:' . sha1($path);
        $known = \Illuminate\Support\Facades\Cache::get($key);
        if ($known !== null) {
            return (bool) $known;
        }

        if ($this->checkUntil === 0.0) {
            $this->checkUntil = microtime(true) + 3;
        }
        if (microtime(true) > $this->checkUntil) {
            return true;
        }

        try {
            if (MediaStore::isRemote($path)) {
                // Only a plain "not here" hides a picture. A refusal, a redirect that minds being
                // asked, or a bad moment at the store says nothing about the file.
                $status = \Illuminate\Support\Facades\Http::timeout(6)->withOptions(['stream' => true])
                    ->withHeaders(['Range' => 'bytes=0-0', 'User-Agent' => 'anee.io media check'])
                    ->get((string) MediaStore::url($path))->status();
                $there = ! in_array($status, [404, 410], true);
            } else {
                $there = \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
            }
        } catch (\Throwable $e) {
            return true;
        }
        \Illuminate\Support\Facades\Cache::put($key, $there ? 1 : 0, $there ? now()->addHours(6) : now()->addMinutes(10));

        return $there;
    }

    /** Clips are offered when at least one of those seasons takes them. The door that saves a clip asks again for its own season. */
    private function importTakesVideo($seasons): bool
    {
        $mine = WorkerContext::seasonIdsWith('video', 'edit');

        return $seasons->contains(fn ($s) => ($mine === null || in_array((int) $s->id, $mine, true))
            && \App\Support\Tier::scheduleCan($s, 'videoRecording'));
    }

    /** Where Import from phone may file: an open season, and for a worker one where they hold the camera. */
    private function importSeasons($schedules)
    {
        $mine = WorkerContext::seasonIdsWith('camera', 'edit');

        return $schedules
            ->filter(fn ($s) => ($mine === null || in_array((int) $s->id, $mine, true)) && ! $s->isLocked())
            ->values();
    }

    /** The seasons this viewer may look at, newest first. */
    private function schedules()
    {
        return AsCroppingSchedule::active()
            ->forClient(WorkerContext::effectiveOwnerId())
            ->orderByDesc('id')
            ->limit(40)
            ->get();
    }

    /**
     * Every album from every season, each still saying which season it is
     * from — an album's name ("the flooded corner") only means something
     * next to the season it belonged to.
     *
     * @return list<array<string,mixed>>
     */
    private function albums($schedules, string $q): array
    {
        $ids = $schedules->pluck('id')->all();
        $titles = $schedules->pluck('title', 'id');

        $rows = \App\Models\AsGalleryAlbum::whereIn('croppingScheduleId', $ids)
            ->where('deleteStatus', 1)
            ->with('images')
            ->orderByDesc('id')
            ->get();

        $out = [];
        foreach ($rows as $a) {
            $season = (string) ($titles[$a->croppingScheduleId] ?? '');
            if ($q !== '' && stripos($a->title . ' ' . $a->description . ' ' . $season, $q) === false) {
                continue;
            }
            $pictures = $a->images
                ->filter(fn ($i) => $this->stillThere($i->path))
                ->map(fn ($i) => [
                    'id' => (int) $i->id,
                    'path' => (string) $i->path,
                    'url' => MediaStore::url($i->path),
                    'caption' => $i->caption,
                    'video' => (bool) preg_match('/\.(mp4|mov|webm|m4v|3gp)$/i', (string) $i->path),
                ])
                ->values()
                ->all();
            // Frames already cut are worn immediately; the rest are asked
            // for by the page, one at a time, and remembered.
            $clipPosters = \App\Support\VideoPoster::urlsFor(
                array_column(array_filter($pictures, fn ($x) => $x['video']), 'path')
            );
            foreach ($pictures as &$px) {
                $px['posterUrl'] = $px['video'] ? ($clipPosters[$px['path']] ?? null) : null;
            }
            unset($px);

            $out[] = [
                'id' => (int) $a->id,
                'title' => $a->title ?: 'Untitled album',
                'description' => $a->description,
                'scheduleId' => (int) $a->croppingScheduleId,
                'scheduleTitle' => $season,
                'count' => count($pictures),
                'cover' => $pictures[0]['url'] ?? null,
                'pictures' => $pictures,
            ];
        }

        return $out;
    }

    /**
     * What every Collab Room made, gathered.
     *
     * @return list<array<string,mixed>>
     */
    private function teamBox($schedules, string $q): array
    {
        $out = [];
        foreach ($schedules as $schedule) {
            foreach (\App\Support\SeasonTeamBox::for($schedule) as $row) {
                if ($q !== '' && stripos($row['title'] . ' ' . $row['kind'] . ' ' . $schedule->title, $q) === false) {
                    continue;
                }
                if (! $this->stillThere($row['path'] ?? null)) {
                    continue;
                }
                $row['scheduleId'] = (int) $schedule->id;
                $row['scheduleTitle'] = (string) $schedule->title;
                $out[] = $row;
            }
            if (count($out) > 600) {
                break;
            }
        }

        usort($out, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return $out;
    }

    /**
     * Everything the viewer's seasons hold, newest first.
     *
     * Walked season by season because SeasonMedia is the single place that
     * knows what counts as a season's media — notes, albums, drawings, saved
     * maps and the rest. Capped, because "everything" on a long-running farm
     * is not a page, it is a download.
     *
     * @param  list<string>  $kinds
     * @return list<array<string,mixed>>
     */
    private function gather(array $kinds, string $q): array
    {
        $schedules = $this->schedules();

        $out = [];
        foreach ($schedules as $schedule) {
            foreach (SeasonMedia::all($schedule) as $m) {
                if ($kinds !== [] && ! $this->wanted((string) ($m['kind'] ?? ''), $kinds)) {
                    continue;
                }
                $title = (string) ($m['title'] ?? '');
                $source = (string) ($m['source'] ?? '');
                if ($q !== '' && stripos($title . ' ' . $source . ' ' . $schedule->title, $q) === false) {
                    continue;
                }
                $path = MediaStore::pathFromUrl($m['url'] ?? null);
                if ($path === null) {
                    // Nothing that cannot be pointed at again: an attachment
                    // that silently resolves nowhere is worse than one absent.
                    continue;
                }
                $out[] = [
                    'kind' => $m['kind'] ?? 'image',
                    'type' => match ($m['kind'] ?? '') { 'video' => 'video', 'audio' => 'audio', default => 'image' },
                    'path' => $path,
                    'poster' => MediaStore::pathFromUrl($m['posterUrl'] ?? null),
                    'url' => $m['url'] ?? null,
                    'posterUrl' => $m['posterUrl'] ?? null,
                    'title' => $title,
                    'source' => $source,
                    // Where the thing itself lives — a drawing opens its pad,
                    // a saved map opens the map with its shapes still editable.
                    // SeasonMedia already worked this out; guessing a route
                    // here would send a grower to the module's front door
                    // instead of to the thing they tapped.
                    'href' => $m['href'] ?? null,
                    'when' => $m['when'] ?? null,
                    'scheduleId' => (int) $schedule->id,
                    'scheduleTitle' => (string) $schedule->title,
                    'sortKey' => (int) ($m['sortKey'] ?? 0),
                ];
            }
            if (count($out) > 1200) {
                break;
            }
        }

        /* And the viewer's own GLOBAL notes — the hub's free-standing notes
         * and every Quick Voice recording, which files there now. They
         * belong to no season, so the season walk above never sees them. */
        $meId = (int) \Illuminate\Support\Facades\Auth::id();
        if ($meId) {
            $globals = \App\Models\AsScheduleNote::active()
                ->where(\App\Support\FarmShelf::mine($meId))
                ->where('croppingScheduleId', 0)
                ->orderByDesc('id')
                ->limit(300)
                ->get();
            // Your maps and drawings live here now (Global and Quick Tools):
            // a map's picture opens the map, a drawing opens the pad.
            $ownSaveByNote = \App\Support\MapAccess::owned($meId)->whereNotNull('noteId')->pluck('id', 'noteId');
            foreach ($globals as $gn) {
                foreach ((is_array($gn->media) ? $gn->media : []) as $i => $m) {
                    if (empty($m['path'])) {
                        continue;
                    }
                    $kind = (string) ($m['type'] ?? 'image');
                    if ($kinds !== [] && ! $this->wanted($kind, $kinds)) {
                        continue;
                    }
                    $title = (string) ($m['title'] ?? $gn->title ?? '');
                    if ($q !== '' && stripos($title . ' ' . $gn->title . ' global note', $q) === false) {
                        continue;
                    }
                    $out[] = [
                        'kind' => $kind,
                        'type' => match ($kind) { 'video' => 'video', 'audio' => 'audio', default => 'image' },
                        'path' => (string) $m['path'],
                        'poster' => $m['poster'] ?? null,
                        'url' => MediaStore::url($m['path']),
                        'posterUrl' => ! empty($m['poster']) ? MediaStore::url($m['poster']) : null,
                        'title' => $title ?: 'Untitled',
                        'source' => ($kind === 'map' ? 'Maps' : ($kind === 'drawing' ? 'Draw' : 'Global note'))
                            . (($noteBy = \App\Support\FarmShelf::by($gn->userId)) ? ' · by ' . $noteBy : ''),
                        'href' => match (true) {
                            $kind === 'map' && isset($ownSaveByNote[$gn->id]) => \App\Support\MapAccess::url((int) $ownSaveByNote[$gn->id]),
                            $kind === 'drawing' => route('draw.page', ['open' => $gn->id . ':' . $i]),
                            default => route('notes.hub', ['open' => $gn->id]),
                        },
                        'when' => $gn->updated_at?->timezone('Asia/Manila')->format('M j, Y'),
                        'scheduleId' => 0,
                        'scheduleTitle' => 'Global notes',
                        'sortKey' => (int) ($gn->updated_at?->timestamp ?? 0),
                    ];
                }
            }
        }

        /* And what Import from phone kept here with no season. A photo taken only
         * to ask Anee a question is also a loose row, and is not shown here. */
        if ($meId) {
            $own = \App\Models\AsGalleryImage::where('croppingScheduleId', 0)
                ->where(\App\Support\FarmShelf::mine($meId))
                ->where('deleteStatus', 1)
                ->where('path', 'not like', '%ai-photos/%')
                ->orderByDesc('id')
                ->limit(600)
                ->get();
            $isClip = fn ($g) => (bool) preg_match('/\.(mp4|mov|webm|mkv|m4v|3gp)$/i', (string) $g->path);
            $ownPosters = \App\Support\VideoPoster::urlsFor($own->filter($isClip)->pluck('path')->all());
            foreach ($own as $g) {
                $kind = $isClip($g) ? 'video' : 'image';
                if ($kinds !== [] && ! $this->wanted($kind, $kinds)) {
                    continue;
                }
                $title = (string) ($g->caption ?: 'Imported from phone');
                if ($q !== '' && stripos($title . ' global gallery imported', $q) === false) {
                    continue;
                }
                $posterUrl = $ownPosters[$g->path] ?? null;
                $out[] = [
                    'kind' => $kind,
                    'type' => $kind,
                    'path' => (string) $g->path,
                    'poster' => MediaStore::pathFromUrl($posterUrl),
                    'url' => MediaStore::url($g->path),
                    'posterUrl' => $posterUrl,
                    'title' => $title,
                    'source' => ($imageBy = \App\Support\FarmShelf::by($g->userId)) ? 'By ' . $imageBy : 'Imported',
                    'href' => null,
                    'when' => $g->created_at?->timezone('Asia/Manila')->format('M j, Y'),
                    'scheduleId' => 0,
                    'scheduleTitle' => 'Global Gallery',
                    'sortKey' => (int) ($g->created_at?->timestamp ?? 0),
                    // Only these can be deleted from here: everything else has its own home to be deleted in.
                    'ownId' => (int) $g->id,
                ];
            }
        }

        /* And what this member has filmed on the community side.
         *
         * A clip shared on the wall, under somebody's post, as a discussion
         * topic or in an answer is a film of this farm like any other — it
         * was simply kept somewhere the gallery never looked. Only the
         * viewer's own, because this shelf is theirs. */
        $me = (int) \Illuminate\Support\Facades\Auth::id();
        if ($me && ($kinds === [] || $this->wanted('video', $kinds))) {
            // A clip answered with out of the gallery is the gallery's clip
            // being pointed at, not a second film: the season's own walk has
            // already put it on this shelf.
            $already = array_flip(array_filter(array_column($out, 'path')));
            foreach (\App\Support\CommunityMedia::videosFor($me, true) as $m) {
                if (isset($already[$m['path']])) {
                    continue;
                }
                if ($q !== '' && stripos(($m['title'] ?? '') . ' ' . ($m['source'] ?? ''), $q) === false) {
                    continue;
                }
                $already[$m['path']] = true;
                $out[] = [
                    'kind' => 'video',
                    'type' => 'video',
                    'path' => $m['path'],
                    'poster' => $m['poster'],
                    'url' => $m['url'],
                    'posterUrl' => $m['posterUrl'],
                    'title' => $m['title'],
                    'source' => $m['source'],
                    'href' => $m['href'],
                    'when' => $m['when'],
                    'scheduleId' => 0,
                    'scheduleTitle' => 'Community',
                    'sortKey' => (int) $m['ts'],
                ];
            }
        }

        // Newest first across every season, which is the order a person
        // actually remembers things in.
        usort($out, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return array_values(array_filter($out, fn ($m) => $this->stillThere($m['path'] ?? null)));
    }

    /** @param  list<string>  $kinds */
    private function wanted(string $kind, array $kinds): bool
    {
        if (in_array($kind, $kinds, true)) {
            return true;
        }

        // Asking for images means pictures, drawings and maps — the same rule
        // the per-season picker uses.
        return in_array('image', $kinds, true) && in_array($kind, ['drawing', 'map'], true);
    }
}
