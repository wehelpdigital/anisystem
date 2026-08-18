<?php

namespace App\Http\Controllers\Manager;

use App\Support\MediaStore;
use App\Support\SeasonMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * What this season already has, offered to whatever is asking for an attachment.
 *
 * Every composer in the app could take a new photo and none of them could take
 * an old one, so the same sack of palay was photographed for the note, again
 * for the observation and again for the report. This lists what has already
 * been kept — the Gallery's answer to "show me everything", handed to the
 * attach bar so a picture can be pointed at instead of taken twice.
 */
class MediaPickerController extends BaseScheduleController
{
    /** How many items the sheet offers in total. */
    private const LIMIT = 300;

    /**
     * One screenful per request. 300 tiles in one payload was 300 images
     * racing each other the moment the sheet opened — the "stuck" the owner
     * reported was the browser paying for pictures nobody had scrolled to.
     */
    private const PAGE = 40;

    public function index(Request $request)
    {
        // A GET, so scheduleFromRequest() collapses to the read gate — the
        // ownership and canView() checks in schedule(), and no write check.
        // Nothing here changes anything; it only says what is already there.
        $schedule = $this->scheduleFromRequest($request);

        $kinds = collect(explode(',', (string) $request->query('kinds', '')))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->all();

        // Search runs here, not in the sheet: the sheet only ever holds the
        // pages it has fetched, and a filter over a third of the list would
        // quietly answer "nothing matches" about photos it never saw.
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $items = collect(SeasonMedia::all($schedule))
            ->filter(fn ($m) => $this->wanted($m['kind'], $kinds))
            ->filter(fn ($m) => $q === ''
                || stripos($m['title'] . ' ' . $m['source'], $q) !== false)
            ->map(fn ($m) => [
                // What it is, for the badge on the tile…
                'kind' => $m['kind'],
                // …and what it is to an attachment, which is a shorter list:
                // a drawing and a saved map are pictures once they are hanging
                // off an observation, and only the module that made them cares
                // about the difference.
                'type' => $m['kind'] === 'video' ? 'video' : 'image',
                'path' => $this->pathFor($m['url']),
                'poster' => $this->pathFor($m['posterUrl']),
                'url' => $m['url'],
                'posterUrl' => $m['posterUrl'],
                'title' => $m['title'],
                'source' => $m['source'],
                'when' => $m['when'],
            ])
            // Something whose path we cannot recover can still be looked at in
            // the Gallery, but it cannot be attached by reference — and an
            // attachment that silently points nowhere is worse than one that
            // was never offered.
            ->filter(fn ($m) => $m['path'] !== null)
            ->take(self::LIMIT)
            ->values();

        $slice = $items->slice(($page - 1) * self::PAGE, self::PAGE)->values()->all();

        return $this->jsonOk('Media loaded.', ['data' => [
            'items' => $slice,
            'more' => $items->count() > $page * self::PAGE,
            'total' => $items->count(),
        ]]);
    }

    // ------------------------------------------------------------------

    /**
     * Asking for images means asking for pictures, drawings and maps included.
     * An empty list means "whatever you have".
     *
     * @param  list<string>  $kinds
     */
    private function wanted(string $kind, array $kinds): bool
    {
        if ($kinds === []) {
            return true;
        }
        if (in_array($kind, $kinds, true)) {
            return true;
        }

        return in_array('image', $kinds, true) && in_array($kind, ['drawing', 'map'], true);
    }

    /**
     * The stored path behind a public URL.
     *
     * SeasonMedia hands out addresses, because everything that reads it only
     * ever wanted to show the file. Picking is the one caller that needs the
     * path back: attaching by path is what makes a second copy unnecessary.
     * Both addresses MediaStore::url() can build are plain prefixes over the
     * path, so stripping the right prefix is an exact inverse rather than a
     * guess — and anything matching neither is left out by the caller.
     */
    private function pathFor(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $mother = (string) config('mother.url');
        if (filled($mother)) {
            $remoteBase = rtrim($mother, '/') . '/storage/';
            if (Str::startsWith($url, $remoteBase)) {
                return MediaStore::REMOTE_PREFIX . Str::after($url, $remoteBase);
            }
        }

        // Asked of the disk rather than assumed: an install serving /storage
        // from a CDN or another host still resolves.
        $localBase = Str::beforeLast(Storage::disk('public')->url('probe.tmp'), 'probe.tmp');

        return Str::startsWith($url, $localBase) ? Str::after($url, $localBase) : null;
    }
}
