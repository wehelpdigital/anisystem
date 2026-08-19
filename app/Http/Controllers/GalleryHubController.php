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
        $slice = array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        $hasMore = count($items) > $page * self::PER_PAGE;

        if ($request->wantsJson() || $request->boolean('json')) {
            return response()->json(['success' => true, 'data' => [
                'items' => $slice,
                'hasMore' => $hasMore,
                'nextPage' => $page + 1,
                'total' => count($items),
            ]]);
        }

        return view('sm.gallery-hub', [
            'items' => $slice,
            'hasMore' => $hasMore,
            'total' => count($items),
            'q' => $q,
        ]);
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
        $schedules = AsCroppingSchedule::active()
            ->forClient(WorkerContext::effectiveOwnerId())
            ->orderByDesc('id')
            ->limit(40)
            ->get();

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
