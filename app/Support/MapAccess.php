<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleNote;
use App\Models\ScheduleMapSave;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Whose map this is, and who else may open it (2026-09-30).
 *
 * Maps moved from the seasons to Global and Quick Tools: a farm's fields do
 * not change when the season does. A saved map is its owner's (scheduleId 0,
 * userId). A season USES a map through a link (as_schedule_map_links), made
 * whenever the map is drawn in that season's Collab Room, worn by one of its
 * lots, attached to one of its activities or day notes, or given one of its
 * tags. The link is what lets the season's team see the map:
 *
 *   view  the owner; or anyone who can open a linked season, with at least
 *         view on the Maps pen there.
 *   edit  the owner; or a worker holding the Maps pen (edit) on a linked
 *         season -- a lot's map is the farm's, and whoever may draw lots
 *         may draw it.
 *
 * Nothing else decides this; every endpoint that reads or writes a save
 * asks here.
 */
class MapAccess
{
    /** The scheduleId every saved map carries now. */
    public const GLOBAL = 0;

    /** The maps a person owns. */
    public static function owned(int $userId): Builder
    {
        return ScheduleMapSave::active()->where('scheduleId', self::GLOBAL)->where('userId', $userId);
    }

    /** The maps a season uses. */
    public static function ofSeason(int $scheduleId): Builder
    {
        return ScheduleMapSave::active()->whereIn('id',
            DB::table('as_schedule_map_links')->where('scheduleId', $scheduleId)->select('saveId'));
    }

    /**
     * The maps someone standing in a season may pick from: the season's own
     * and every map of their own. What a lot, an activity or a day note
     * offers when it asks "which map?".
     */
    public static function choices(int $scheduleId, ?int $userId = null): Builder
    {
        $userId ??= (int) Auth::id();

        return ScheduleMapSave::active()->where(function ($q) use ($scheduleId, $userId) {
            $q->whereIn('id', DB::table('as_schedule_map_links')->where('scheduleId', $scheduleId)->select('saveId'))
                ->orWhere(fn ($w) => $w->where('scheduleId', self::GLOBAL)->where('userId', $userId));
        });
    }

    /**
     * What the global Maps page lists: your own maps, and -- for a worker
     * holding the Maps pen on a farm -- that farm's maps in the seasons they
     * can open.
     */
    public static function shelf(?int $userId = null): Builder
    {
        $userId ??= (int) Auth::id();
        $farm = self::farmSeasonIds();

        return ScheduleMapSave::active()->where(function ($q) use ($userId, $farm) {
            $q->where(fn ($w) => $w->where('scheduleId', self::GLOBAL)->where('userId', $userId));
            if ($farm) {
                $q->orWhereIn('id', DB::table('as_schedule_map_links')->whereIn('scheduleId', $farm)->select('saveId'));
            }
        });
    }

    /** Remember that a season uses a map. Idempotent. */
    public static function link(int $saveId, int $scheduleId): void
    {
        if ($saveId <= 0 || $scheduleId <= 0) {
            return;
        }
        DB::table('as_schedule_map_links')->insertOrIgnore([
            'saveId' => $saveId, 'scheduleId' => $scheduleId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** The seasons a map is linked to. */
    public static function seasonIds(ScheduleMapSave $save): array
    {
        return DB::table('as_schedule_map_links')->where('saveId', $save->id)->pluck('scheduleId')
            ->map(fn ($id) => (int) $id)->all();
    }

    public static function isOwner(ScheduleMapSave $save, ?int $userId = null): bool
    {
        $userId ??= (int) Auth::id();

        return (int) $save->scheduleId === self::GLOBAL && (int) $save->userId === $userId;
    }

    public static function canView(ScheduleMapSave $save): bool
    {
        if (self::isOwner($save)) {
            return true;
        }

        return WorkerContext::canView() && WorkerContext::canUseModule('maps') && self::sharesASeason($save);
    }

    public static function canEdit(ScheduleMapSave $save): bool
    {
        if (self::isOwner($save)) {
            return true;
        }

        return WorkerContext::canView() && WorkerContext::canWriteModule('maps') && self::sharesASeason($save);
    }

    /** A save by id that the caller may open, or null. */
    public static function viewable($id): ?ScheduleMapSave
    {
        $save = (int) $id > 0 ? ScheduleMapSave::active()->find((int) $id) : null;

        return ($save && self::canView($save)) ? $save : null;
    }

    /** A save by id that the caller may change, or null. */
    public static function editable($id): ?ScheduleMapSave
    {
        $save = (int) $id > 0 ? ScheduleMapSave::active()->find((int) $id) : null;

        return ($save && self::canEdit($save)) ? $save : null;
    }

    /** Where a saved map opens. */
    public static function url(int $saveId, array $extra = []): string
    {
        return route('maps.page', array_merge(['save' => $saveId], $extra));
    }

    /** A picture for the shelf, always answerable (see ScheduleMapController::thumb). */
    public static function thumbUrl(int $saveId): string
    {
        return route('sm.map.thumb', ['id' => $saveId]);
    }

    /**
     * The picture a save filed in its note, if any. The note is where the
     * map's description lives too.
     */
    public static function picturePath(?AsScheduleNote $note): ?string
    {
        foreach ((is_array($note?->media) ? $note->media : []) as $m) {
            $path = (string) ($m['path'] ?? '');
            if ($path !== '' && (($m['type'] ?? '') === 'map' || preg_match('~/map-[A-Za-z0-9]+\.png$~', $path))) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Picture path -> "open this saved map" address, for the maps a season
     * uses. A note or a day that carries a map carries its PICTURE; the save
     * it came from is what can actually be reopened, so the way back is
     * found by matching the picture to its save.
     */
    public static function urlsByPicture(int $scheduleId): array
    {
        $saves = self::ofSeason($scheduleId)->orderByDesc('id')->get(['id', 'noteId']);
        $notes = AsScheduleNote::active()->whereIn('id', $saves->pluck('noteId')->filter()->all())->get()->keyBy('id');
        $out = [];
        foreach ($saves as $save) {
            foreach ((is_array($notes->get($save->noteId)?->media) ? $notes->get($save->noteId)->media : []) as $m) {
                $path = (string) ($m['path'] ?? '');
                $isMap = ($m['type'] ?? '') === 'map' || preg_match('~/map-[A-Za-z0-9]+\.png$~', $path);
                if ($path !== '' && $isMap && ! isset($out[$path])) {
                    $out[$path] = self::url((int) $save->id);
                }
            }
        }

        return $out;
    }

    /** Where a map's note is read: its season's notebook, or Global Notes. */
    public static function noteHref(?AsScheduleNote $note): ?string
    {
        if (! $note) {
            return null;
        }

        return (int) $note->croppingScheduleId > 0
            ? route('sm.notes', ['id' => $note->croppingScheduleId, 'open' => $note->id])
            : route('notes.hub', ['open' => $note->id]);
    }

    /**
     * The saved maps as every shelf lists them -- the Maps page, the Collab
     * Room's menu, a lot's picker, a day's "Add a map".
     *
     * @param  iterable<ScheduleMapSave>  $rows
     */
    public static function rows(iterable $rows): array
    {
        $rows = collect($rows);
        $me = (int) Auth::id();
        $users = User::whereIn('id', $rows->pluck('userId')->unique())->get()->keyBy('id');
        $notes = AsScheduleNote::active()->whereIn('id', $rows->pluck('noteId')->filter()->all())->get()->keyBy('id');
        $seasons = AsCroppingSchedule::whereIn('id', $rows->pluck('originScheduleId')->filter()->unique())->pluck('title', 'id');

        return $rows->map(function (ScheduleMapSave $r) use ($users, $notes, $seasons, $me) {
            $note = $r->noteId ? $notes->get($r->noteId) : null;
            $path = self::picturePath($note);
            // What the note says the map was for, with the boilerplate line
            // the save itself wrote taken back off.
            $words = trim(strip_tags((string) ($note?->body)));
            $words = trim((string) preg_replace('/Saved (team )?map(?: —|[.,:])? [Tt]ap View map to open it\.?\s*$/u', '', $words));

            return [
                'id' => (int) $r->id,
                'title' => $r->title,
                'description' => $words,
                'by' => (string) Str::of(optional($users->get($r->userId))->full_name ?? 'Someone')->explode(' ')->first(),
                'mine' => (int) $r->userId === $me,
                'when' => $r->created_at?->timezone('Asia/Manila')->format('M j, Y g:ia'),
                'count' => count(json_decode((string) $r->objects, true) ?: []),
                // Drawn in a Collab Room (the team's) or on your own.
                'source' => $r->source === 'team' ? 'team' : 'solo',
                'season' => $r->originScheduleId ? ($seasons[$r->originScheduleId] ?? null) : null,
                'imagePath' => $path,
                'imageUrl' => MediaStore::url($path),
                // A map with no shapes and no picture has nothing to draw:
                // its card keeps the map mark instead of asking for a 404.
                'thumbUrl' => ($path || json_decode((string) $r->objects, true)) ? self::thumbUrl((int) $r->id) : null,
                'url' => self::url((int) $r->id),
                'noteHref' => self::noteHref($note),
            ];
        })->values()->all();
    }

    // ------------------------------------------------------------------

    /** The seasons of the farm this person is standing in that they may see maps of. */
    private static function farmSeasonIds(): array
    {
        if (! WorkerContext::inWorkerContext() || ! WorkerContext::canView() || ! WorkerContext::canUseModule('maps')) {
            return [];
        }

        return AsCroppingSchedule::active()->forClient(WorkerContext::effectiveOwnerId())->pluck('id')
            ->map(fn ($id) => (int) $id)->all();
    }

    /** Whether the map is linked to a season the caller can open. */
    private static function sharesASeason(ScheduleMapSave $save): bool
    {
        $linked = self::seasonIds($save);
        if (! $linked) {
            return false;
        }

        return AsCroppingSchedule::active()->forClient(WorkerContext::effectiveOwnerId())
            ->whereIn('id', $linked)->exists();
    }
}
