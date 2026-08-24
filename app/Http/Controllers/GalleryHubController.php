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
 */
class GalleryHubController extends Controller
{
    /** How many tiles arrive at once. */
    private const PER_PAGE = 24;

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
        $tab = in_array($request->query('tab'), ['albums', 'videos', 'team'], true)
            ? $request->query('tab')
            : 'all';

        $schedules = $this->schedules();
        $stills = array_values(array_filter($items, fn ($m) => $m['type'] !== 'video'));
        $clips = array_values(array_filter($items, fn ($m) => $m['type'] === 'video'));

        $shelf = match ($tab) {
            'albums' => $this->albums($schedules, $q),
            'team' => $this->teamBox($schedules, $q),
            'videos' => $clips,
            default => $stills,
        };

        $paged = $tab === 'albums' ? $shelf : array_slice($shelf, 0, self::PER_PAGE);

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
                // Counted only when its own shelf is open; the button shows a
                // dash otherwise rather than a number bought at that price.
                'albums' => $tab === 'albums' ? count($shelf) : null,
                'team' => $tab === 'team' ? count($shelf) : null,
            ],
            'q' => $q,
        ]);
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
                ->map(fn ($i) => [
                    'id' => (int) $i->id,
                    'url' => MediaStore::url($i->path),
                    'caption' => $i->caption,
                    'video' => (bool) preg_match('/\.(mp4|mov|webm|m4v|3gp)$/i', (string) $i->path),
                ])
                ->values()
                ->all();

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
                    'type' => ($m['kind'] ?? '') === 'video' ? 'video' : 'image',
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

        /* And what this member has filmed on the community side.
         *
         * A clip shared on the wall, under somebody's post, as a discussion
         * topic or in an answer is a film of this farm like any other — it
         * was simply kept somewhere the gallery never looked. Only the
         * viewer's own, because this shelf is theirs. */
        $me = (int) \Illuminate\Support\Facades\Auth::id();
        if ($me && ($kinds === [] || $this->wanted('video', $kinds))) {
            foreach (\App\Support\CommunityMedia::videosFor($me, true) as $m) {
                if ($q !== '' && stripos(($m['title'] ?? '') . ' ' . ($m['source'] ?? ''), $q) === false) {
                    continue;
                }
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

        return $out;
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
