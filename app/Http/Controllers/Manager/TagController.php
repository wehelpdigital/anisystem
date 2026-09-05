<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleTag;
use App\Models\AsScheduleTagLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Tags: the words a farmer ties to the season's things.
 *
 * The picker (partials/tag-picker) talks to list/store; the Tags module
 * page browses everything a tag is tied to; each save endpoint ties and
 * unties through ScheduleTags::sync().
 */
class TagController extends BaseScheduleController
{
    /** The Tags module page. */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        return view('sm.tags', ['schedule' => $schedule]);
    }

    /** The schedule's tags, with how many things each one ties. */
    public function list(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $tags = AsScheduleTag::forSchedule($schedule->id)
            ->where('deleteStatus', 1)
            ->withCount('links')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name, 'count' => (int) $t->links_count]);

        return response()->json(['success' => true, 'data' => ['tags' => $tags]]);
    }

    /** Coin a tag (or revive/return the one that already wears this name). */
    public function store(Request $request)
    {
        $this->assertCanEdit();
        $schedule = $this->schedule($request->input('scheduleId'));
        $name = trim((string) $request->input('name'));
        $name = mb_substr(preg_replace('/\s+/u', ' ', $name), 0, 60);
        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'A tag needs a name.'], 422);
        }

        $tag = AsScheduleTag::forSchedule($schedule->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
        if ($tag) {
            if ((int) $tag->deleteStatus !== 1) {
                $tag->update(['deleteStatus' => 1]);
            }
        } else {
            $tag = AsScheduleTag::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'name' => $name,
                'deleteStatus' => 1,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Tag ready.', 'data' => [
            'tag' => ['id' => (int) $tag->id, 'name' => $tag->name, 'count' => (int) $tag->links()->count()],
        ]]);
    }

    /** Retire a tag. Its ties go with it; the things themselves stay. */
    public function destroy(Request $request, $id)
    {
        $this->assertCanEdit();
        $schedule = $this->schedule($request->input('scheduleId') ?: $request->query('scheduleId'));
        $tag = AsScheduleTag::forSchedule($schedule->id)->where('id', (int) $id)->first();
        if (! $tag) {
            return response()->json(['success' => false, 'message' => 'That tag is not here.'], 404);
        }
        $tag->update(['deleteStatus' => 0]);
        AsScheduleTagLink::where('tagId', $tag->id)->delete();

        return response()->json(['success' => true, 'message' => 'Tag removed.']);
    }

    /** One thing's tags — what an edit sheet asks when it opens. */
    public function of(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $kind = (string) $request->query('kind');
        $refId = (int) $request->query('refId');
        // A day note is asked for by its date — the sheet opens knowing the
        // day, not the row. Resolve against the active version, the same way
        // the save does.
        if ($kind === 'daynote' && $refId <= 0 && $request->query('date')) {
            $ver = \App\Models\AsScheduleActivityVersion::active()
                ->forSchedule($schedule->id)->where('isActive', 1)->orderBy('id')->value('id');
            $refId = (int) \App\Models\AsScheduleDateNote::active()
                ->forSchedule($schedule->id)
                ->when($ver, fn ($q) => $q->forVersion($ver))
                ->whereDate('noteDate', (string) $request->query('date'))
                ->value('id');
        }
        if (! in_array($kind, AsScheduleTagLink::KINDS, true) || $refId <= 0) {
            return response()->json(['success' => true, 'data' => ['tags' => []]]);
        }
        $tags = AsScheduleTagLink::where('kind', $kind)->where('refId', $refId)
            ->join('as_schedule_tags', 'as_schedule_tags.id', '=', 'as_schedule_tag_links.tagId')
            ->where('as_schedule_tags.croppingScheduleId', $schedule->id)
            ->where('as_schedule_tags.deleteStatus', 1)
            ->orderBy('as_schedule_tags.name')
            ->get(['as_schedule_tags.id', 'as_schedule_tags.name'])
            ->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name]);

        return response()->json(['success' => true, 'data' => ['tags' => $tags]]);
    }

    /** Everything one tag is tied to, said plainly and grouped by kind. */
    public function items(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $tag = AsScheduleTag::forSchedule($schedule->id)
            ->where('deleteStatus', 1)->where('id', (int) $request->query('tagId'))->first();
        if (! $tag) {
            return response()->json(['success' => false, 'message' => 'That tag is not here.'], 404);
        }

        $links = AsScheduleTagLink::where('tagId', $tag->id)->get(['kind', 'refId']);
        $byKind = [];
        foreach ($links as $l) {
            $byKind[$l->kind][] = (int) $l->refId;
        }

        $items = [];
        foreach ($byKind as $kind => $refIds) {
            foreach ($this->describe($schedule, $kind, $refIds) as $row) {
                $items[] = $row;
            }
        }
        usort($items, fn ($x, $y) => strcmp($y['when'] ?? '', $x['when'] ?? ''));

        return response()->json(['success' => true, 'data' => [
            'tag' => ['id' => (int) $tag->id, 'name' => $tag->name],
            'items' => array_values($items),
        ]]);
    }

    /**
     * Say what each linked thing is: [{kind, refId, icon, title, sub, when, url}].
     * Only living rows come back — a deleted activity drops out silently.
     */
    private function describe($schedule, string $kind, array $refIds): array
    {
        $out = [];
        $boardUrl = fn ($module) => route('sm.activities', ['id' => $schedule->id, 'module' => $module]);
        $day = fn ($d) => $d ? \Carbon\Carbon::parse((string) $d)->format('M j, Y') : null;

        switch ($kind) {
            case 'activity':
                foreach (\App\Models\AsScheduleActivity::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $a) {
                    $out[] = ['kind' => 'activity', 'refId' => (int) $a->id, 'icon' => '⚡',
                        'title' => (string) $a->activityTitle,
                        'sub' => trim(($a->activityType ?: 'activity') . ($a->isDone ? ' · done' : '')),
                        'when' => optional($a->targetDate)->format('Y-m-d'),
                        'url' => route('sm.activities', ['id' => $schedule->id]) . '&highlight=' . $a->id];
                }
                break;
            case 'expense':
                foreach (\App\Models\AsScheduleDayExpense::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $e) {
                    $out[] = ['kind' => 'expense', 'refId' => (int) $e->id, 'icon' => '💸',
                        'title' => (trim((string) $e->note) ?: 'Expense') . ' — ₱' . number_format((float) $e->amount, 2),
                        'sub' => 'expense · ' . $day($e->expenseDate),
                        'when' => (string) $e->expenseDate,
                        'url' => route('sm.activities', ['id' => $schedule->id])];
                }
                break;
            case 'income':
                foreach (\App\Models\AsScheduleDayIncome::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $i) {
                    $out[] = ['kind' => 'income', 'refId' => (int) $i->id, 'icon' => '💰',
                        'title' => (trim((string) ($i->title ?: $i->note)) ?: 'Income') . ' — ₱' . number_format((float) $i->amount, 2),
                        'sub' => 'income · ' . $day($i->incomeDate),
                        'when' => (string) $i->incomeDate,
                        'url' => route('sm.activities', ['id' => $schedule->id])];
                }
                break;
            case 'move':
                foreach (\App\Models\AsInventoryMove::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->with('item')->get() as $m) {
                    $in = (float) $m->delta >= 0;
                    $qty = rtrim(rtrim(number_format(abs((float) ($m->enteredQty ?? $m->delta)), 2), '0'), '.');
                    $out[] = ['kind' => 'move', 'refId' => (int) $m->id, 'icon' => '📦',
                        'title' => ($m->item?->name ?: 'Stock') . ' — ' . ($in ? '+' : '−') . $qty . ' ' . ($m->enteredUnit ?: ''),
                        'sub' => 'stock ' . ($in ? 'in' : 'out') . ' · ' . $day($m->happenedOn ?? $m->created_at),
                        'when' => (string) ($m->happenedOn ?: $m->created_at?->format('Y-m-d')),
                        'url' => $boardUrl('inventory')];
                }
                break;
            case 'daynote':
                foreach (\App\Models\AsScheduleDateNote::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $n) {
                    $out[] = ['kind' => 'daynote', 'refId' => (int) $n->id, 'icon' => '📝',
                        'title' => mb_substr(trim(strip_tags((string) $n->noteContent)) ?: 'Day note', 0, 90),
                        'sub' => 'day note · ' . $day($n->noteDate),
                        'when' => optional($n->noteDate)->format('Y-m-d'),
                        'url' => route('sm.activities', ['id' => $schedule->id])];
                }
                break;
            case 'note':
                foreach (\App\Models\AsScheduleNote::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $n) {
                    $draw = collect(is_array($n->media) ? $n->media : [])->contains(fn ($m) => ($m['type'] ?? '') === 'drawing');
                    $out[] = ['kind' => 'note', 'refId' => (int) $n->id, 'icon' => $draw ? '✏️' : '📓',
                        'title' => trim((string) $n->title) ?: mb_substr(trim(strip_tags((string) $n->body)) ?: 'Note', 0, 90),
                        'sub' => ($draw ? 'drawing · ' : 'note · ') . $n->created_at?->format('M j, Y'),
                        'when' => $n->created_at?->format('Y-m-d'),
                        'url' => $boardUrl('notes')];
                }
                break;
            case 'inote':
                foreach (\App\Models\AsInlineNote::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $n) {
                    $media = collect(is_array($n->media) ? $n->media : []);
                    $icon = $media->contains(fn ($m) => ($m['type'] ?? '') === 'drawing') ? '✏️'
                        : ($media->contains(fn ($m) => ($m['type'] ?? '') === 'video') ? '🎬'
                        : ($media->isNotEmpty() ? '📷' : '🗒️'));
                    $out[] = ['kind' => 'inote', 'refId' => (int) $n->id, 'icon' => $icon,
                        'title' => trim((string) $n->title) ?: (mb_substr(trim(strip_tags((string) $n->content)), 0, 90) ?: 'Board note'),
                        'sub' => 'board note · ' . $day($n->noteDate ?: $n->created_at),
                        'when' => (string) ($n->noteDate ?: $n->created_at?->format('Y-m-d')),
                        'url' => route('sm.activities', ['id' => $schedule->id])];
                }
                break;
            case 'item':
                foreach (\App\Models\AsInventoryItem::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $i) {
                    $out[] = ['kind' => 'item', 'refId' => (int) $i->id, 'icon' => '📦',
                        'title' => (string) $i->name,
                        'sub' => trim(($i->kind ?: 'inventory item') . ($i->unit ? ' · counted in ' . $i->unit : '')),
                        'when' => $i->created_at?->format('Y-m-d'),
                        'url' => $boardUrl('inventory')];
                }
                break;
            case 'map':
                foreach (\App\Models\ScheduleMapSave::whereIn('id', $refIds)
                    ->where('scheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $m) {
                    $out[] = ['kind' => 'map', 'refId' => (int) $m->id, 'icon' => '🗺️',
                        'title' => trim((string) $m->title) ?: 'Saved map',
                        'sub' => 'map · ' . $m->created_at?->format('M j, Y'),
                        'when' => $m->created_at?->format('Y-m-d'),
                        'url' => $boardUrl('maps')];
                }
                break;
        }

        return $out;
    }
}
