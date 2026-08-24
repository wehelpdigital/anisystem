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
    /**
     * The frame for one clip, cut and kept if it has never been cut.
     *
     * The list cannot do this for every film it returns — ffmpeg per row
     * would make opening the picker a wait — so the sheet asks tile by tile,
     * and only for the clips that have no frame of their own. The second time
     * anybody asks about the same clip it is a row in a table.
     */
    public function poster(Request $request)
    {
        $asked = trim((string) $request->input('path'));

        /* A frame already made is handed straight back.
         *
         * The full check below asks whether the clip is readable from HERE,
         * which is the right question before cutting anything and the wrong
         * one for a picture that was cut long ago: a clip whose file has
         * since moved still has a thumbnail, and showing it beats showing a
         * clapperboard. The lookup key had to be a path this app trusted once
         * to be in that table at all, and nothing but a name is read from it.
         */
        if ($asked !== '' && ! str_contains($asked, '..') && ! str_contains($asked, '://')) {
            if ($known = \App\Support\VideoPoster::stored($asked)) {
                return $this->jsonOk('Frame ready.', ['data' => [
                    'poster'    => $known,
                    'posterUrl' => \App\Support\MediaStore::url($known),
                    'why'       => null,
                ]]);
            }
        }

        $path = \App\Support\GalleryPick::path(
            $asked,
            \App\Support\GalleryPick::VIDEO_EXTS
        );
        if ($path === null) {
            return $this->jsonFail('That is not a clip this app keeps.', 422);
        }

        /* Finish even if the phone stops waiting.
         *
         * Cutting a frame out of a 190 MB recording takes as long as it
         * takes, and a browser or a proxy may give up first. The frame is
         * kept when it is made, so an abandoned request is not wasted work:
         * the next time anybody opens this picker it is already there. */
        @ignore_user_abort(true);
        @set_time_limit(180);

        $poster = \App\Support\VideoPoster::ensure($path);

        /* Why, when the answer is no.
         *
         * A picker showing clapperboards looks the same whether this server
         * has no ffmpeg, cannot reach the clip, or simply failed on it — and
         * they are three different things to fix. The reason travels with the
         * answer so the next report of "no thumbnails" comes with its cause
         * attached.
         */
        $why = null;
        if (! $poster) {
            $why = \App\Support\VideoOptimizer::usableBinary() ? 'no-frame' : 'no-ffmpeg';
        }

        return $this->jsonOk('Frame ready.', ['data' => [
            'poster'    => $poster,
            'posterUrl' => $poster ? \App\Support\MediaStore::url($poster) : null,
            'why'       => $why,
        ]]);
    }

    /**
     * A frame the browser cut, kept for everyone who cannot cut one.
     *
     * The picker falls back to drawing a frame itself when this server says
     * it has no way to — no ffmpeg, or a clip it cannot read — and hands the
     * picture back here so the next phone, which may refuse to decode a
     * 190 MB film for a thumbnail, is simply shown a picture.
     */
    public function posterSave(Request $request)
    {
        $path = \App\Support\GalleryPick::path(
            (string) $request->input('path'),
            \App\Support\GalleryPick::VIDEO_EXTS
        );
        if ($path === null) {
            return $this->jsonFail('That is not a clip this app keeps.', 422);
        }
        // Already answered by somebody faster: keep the first one.
        if ($already = \App\Support\VideoPoster::stored($path)) {
            return $this->jsonOk('Frame kept.', ['data' => [
                'poster' => $already, 'posterUrl' => \App\Support\MediaStore::url($already),
            ]]);
        }

        $data = (string) $request->input('image', '');
        if (! preg_match('~^data:image/(jpeg|png|webp);base64,~', $data)) {
            return $this->jsonFail('That is not a picture.', 422);
        }
        $binary = base64_decode(substr($data, strpos($data, ',') + 1), true);
        // A frame is a small thing; anything past this is not one.
        if ($binary === false || strlen($binary) < 512 || strlen($binary) > 3 * 1024 * 1024) {
            return $this->jsonFail('That picture could not be read.', 422);
        }

        $poster = \App\Support\VideoPoster::keep($path, $binary);

        return $this->jsonOk('Frame kept.', ['data' => [
            'poster'    => $poster,
            'posterUrl' => $poster ? \App\Support\MediaStore::url($poster) : null,
        ]]);
    }

    private function pathFor(?string $url): ?string
    {
        // One truth, shared with the AI's gallery references — the list this
        // picker offers is exactly the list the ask endpoint will honour.
        return MediaStore::pathFromUrl($url);
    }
}
