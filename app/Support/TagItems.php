<?php

namespace App\Support;

/**
 * What a season tag is tied to, said plainly: one row per living thing
 * with its icon, title, a short line, a date and the door that opens it.
 *
 * Lifted out of the season Tags module (Manager\TagController) so the
 * Global Tags page (GlobalTagController) can say the same thing the same
 * way for every season a member owns. Every kind in
 * AsScheduleTagLink::KINDS needs its case here, or its rows come back blank.
 */
class TagItems
{
    /**
     * Say what each linked thing is: [{kind, refId, icon, title, sub, when, url}].
     * Only living rows come back — a deleted activity drops out silently.
     */
    public static function describe($schedule, string $kind, array $refIds): array
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
                        'url' => route('sm.activities', ['id' => $schedule->id, 'highlight' => $a->id])];
                }
                break;
            case 'expense':
                foreach (\App\Models\AsScheduleDayExpense::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $e) {
                    $out[] = ['kind' => 'expense', 'refId' => (int) $e->id, 'icon' => '💸',
                        'title' => (trim((string) $e->note) ?: 'Expense') . ' — ' . \App\Support\Region::symbol() . number_format((float) $e->amount, 2),
                        'sub' => 'expense · ' . $day($e->expenseDate),
                        'when' => (string) $e->expenseDate,
                        'url' => route('sm.activities', ['id' => $schedule->id, 'day' => substr((string) $e->expenseDate, 0, 10)])];
                }
                break;
            case 'income':
                foreach (\App\Models\AsScheduleDayIncome::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $i) {
                    $out[] = ['kind' => 'income', 'refId' => (int) $i->id, 'icon' => '💰',
                        'title' => (trim((string) ($i->title ?: $i->note)) ?: 'Income') . ' — ' . \App\Support\Region::symbol() . number_format((float) $i->amount, 2),
                        'sub' => 'income · ' . $day($i->incomeDate),
                        'when' => (string) $i->incomeDate,
                        'url' => route('sm.activities', ['id' => $schedule->id, 'day' => substr((string) $i->incomeDate, 0, 10)])];
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
                        'url' => route('sm.inventory', ['id' => $schedule->id, 'move' => $m->id])];
                }
                break;
            case 'daynote':
                foreach (\App\Models\AsScheduleDateNote::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $n) {
                    $out[] = ['kind' => 'daynote', 'refId' => (int) $n->id, 'icon' => '📝',
                        'title' => mb_substr(trim(strip_tags((string) $n->noteContent)) ?: 'Day note', 0, 90),
                        'sub' => 'day note · ' . $day($n->noteDate),
                        'when' => optional($n->noteDate)->format('Y-m-d'),
                        'url' => route('sm.activities', array_filter(['id' => $schedule->id, 'day' => optional($n->noteDate)->format('Y-m-d')]))];
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
                        'url' => route('sm.notes', ['id' => $schedule->id, 'open' => $n->id])];
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
                        'url' => route('sm.activities', array_filter(['id' => $schedule->id, 'day' => substr((string) ($n->noteDate ?: $n->created_at?->format('Y-m-d')), 0, 10)]))];
                }
                break;
            case 'image':
                foreach (\App\Models\AsGalleryImage::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $g) {
                    $clip = (bool) preg_match('/\.(mp4|webm|mov|m4v)$/i', (string) $g->path);
                    $out[] = ['kind' => 'image', 'refId' => (int) $g->id, 'icon' => $clip ? '🎬' : '📷',
                        'title' => trim((string) $g->caption) ?: ($clip ? 'Clip' : 'Photo'),
                        'sub' => ($clip ? 'clip · ' : 'photo · ') . $day($g->created_at),
                        'when' => $g->created_at?->format('Y-m-d'),
                        'url' => route('sm.gallery', ['id' => $schedule->id])];
                }
                break;
            case 'item':
                foreach (\App\Models\AsInventoryItem::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $i) {
                    $out[] = ['kind' => 'item', 'refId' => (int) $i->id, 'icon' => '📦',
                        'title' => (string) $i->name,
                        'sub' => trim(($i->kind ?: 'inventory item') . ($i->unit ? ' · counted in ' . $i->unit : '')),
                        'when' => $i->created_at?->format('Y-m-d'),
                        'url' => route('sm.inventory', ['id' => $schedule->id, 'open' => $i->id])];
                }
                break;
            case 'map':
                foreach (\App\Models\ScheduleMapSave::whereIn('id', $refIds)
                    ->where('scheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $m) {
                    $out[] = ['kind' => 'map', 'refId' => (int) $m->id, 'icon' => '🗺️',
                        'title' => trim((string) $m->title) ?: 'Saved map',
                        'sub' => 'map · ' . $m->created_at?->format('M j, Y'),
                        'when' => $m->created_at?->format('Y-m-d'),
                        'url' => route('sm.maps', ['id' => $schedule->id, 'save' => $m->id])];
                }
                break;
            case 'doc':
                foreach (\App\Models\AsScheduleDocEntry::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $d) {
                    $out[] = ['kind' => 'doc', 'refId' => (int) $d->id, 'icon' => '📄',
                        'title' => trim((string) $d->title) ?: (string) $d->type_label,
                        'sub' => 'document · ' . $d->type_label,
                        'when' => $d->created_at?->format('Y-m-d'),
                        'url' => route('sm.documentation', ['id' => $schedule->id, 'open' => $d->id])];
                }
                break;
            case 'worker':
                foreach (\App\Models\AsScheduleWorker::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $w) {
                    $out[] = ['kind' => 'worker', 'refId' => (int) $w->id, 'icon' => '🧑‍🌾',
                        'title' => (string) $w->workerName,
                        'sub' => trim('worker' . ($w->skills ? ' · ' . implode(', ', array_slice((array) $w->skills, 0, 3)) : '')),
                        'when' => $w->created_at?->format('Y-m-d'),
                        'url' => route('sm.workers', ['id' => $schedule->id, 'open' => $w->id])];
                }
                break;
            case 'lot':
                foreach (\App\Models\AsScheduleLot::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $l) {
                    $out[] = ['kind' => 'lot', 'refId' => (int) $l->id, 'icon' => '🌾',
                        'title' => (string) $l->lotName,
                        'sub' => trim('lot' . ($l->crop ? ' · ' . $l->crop : '') . ($l->variety ? ' · ' . $l->variety : '')),
                        'when' => $l->created_at?->format('Y-m-d'),
                        'url' => route('sm.lots', ['id' => $schedule->id, 'open' => $l->id])];
                }
                break;
            case 'report':
                foreach (\App\Models\AsFarmReport::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $fr) {
                    $out[] = ['kind' => 'report', 'refId' => (int) $fr->id, 'icon' => '📊',
                        'title' => trim((string) $fr->title) ?: 'Saved report',
                        'sub' => 'report · ' . $fr->kind,
                        'when' => $fr->created_at?->format('Y-m-d'),
                        'url' => self::reportUrl($schedule, $fr)];
                }
                break;
            case 'observation':
                foreach (\App\Models\AsSchedulePostHarvest::whereIn('id', $refIds)
                    ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->get() as $o) {
                    $out[] = ['kind' => 'observation', 'refId' => (int) $o->id, 'icon' => '📝',
                        'title' => trim((string) ($o->title ?? '')) ?: 'Observation',
                        'sub' => 'observation · ' . $o->created_at?->format('M j, Y'),
                        'when' => $o->created_at?->format('Y-m-d'),
                        'url' => route('sm.post-harvest', ['id' => $schedule->id, 'open' => $o->id])];
                }
                break;
        }

        return $out;
    }

    /**
     * The page a saved report actually lives on, by its kind — and, where
     * that page can open a named save (the Anee reports), the exact one.
     */
    public static function reportUrl($schedule, \App\Models\AsFarmReport $fr): string
    {
        return match ($fr->kind) {
            'labor' => route('sm.labor.report', ['id' => $schedule->id]),
            'expenses' => route('sm.expenses.report', ['id' => $schedule->id]),
            'profit' => route('sm.profit.report', ['id' => $schedule->id]),
            'protocol' => route('sm.protocol.report', ['id' => $schedule->id, 'open' => $fr->id]),
            'compare' => route('compare.page', ['open' => $fr->id]),
            'season' => route('sm.anee.season', ['id' => $schedule->id, 'open' => $fr->id]),
            'sofar' => route('sm.anee.sofar', ['id' => $schedule->id, 'open' => $fr->id]),
            default => route('sm.reports', ['id' => $schedule->id]),
        };
    }
}
