<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The global Trash (2026-10-23, the owner: "have a global trash module. now
 * all deleted items will go to trash and will be permanently delete after 30
 * days").
 *
 * Every module already deletes by setting a row's deleteStatus to 0. The
 * kinds below are the things a farmer deletes on purpose (a season, an
 * activity, a lot, a note, a photo, a saved analysis, a contact...); the
 * bookkeeping rows that are soft deleted as a matter of course (activity item
 * lines rewritten on every save, board events, ledgers) are not among them
 * and never reach the Trash.
 *
 * as_trash is the book of those deletions. A row lands there the moment its
 * model is saved with deleteStatus 0 (BaseModel's update event, exact time
 * and person), and the sweep finds the rest, deleted by a mass update (an
 * album's photos, a board version's activities), within minutes. A season's
 * things belong to the season's owner, whoever deleted them.
 *
 * What was deleted with its parent (in the same couple of minutes: the
 * photos with their album, the activities with their board version, the
 * things of a deleted season) is listed under the parent, and comes back or
 * goes for good with it. Thirty days after it was deleted, a thing is deleted
 * for good: its row and everything that hangs on it (a season takes its
 * whole board, notes, lots, money and photos), and a photo's file.
 */
class Trash
{
    public const DAYS = 30;

    /** Deleted within this many seconds of its parent: deleted with it. */
    private const WITH = 180;

    /**
     * kind => table, label, group, owner [user|season, column], title column(s), subtitle column, parent [table, column].
     * `user` names the owner of a season kind's own rows outside any season (a Global Note, a Gallery photo: croppingScheduleId 0).
     * `skip` names the deletes the app makes by itself, never the farmer's: a run that failed delists itself
     * (`failed`), and an activity's own money line goes when the activity is undone (`sourced`).
     */
    public const KINDS = [
        'season' => ['table' => 'as_cropping_schedules', 'label' => 'Cropping schedule', 'group' => 'seasons', 'owner' => ['user', 'anisystemUserId'], 'title' => ['title'], 'sub' => 'cropType'],
        'version' => ['table' => 'as_schedule_activity_versions', 'label' => 'Board version', 'group' => 'board', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['versionName']],
        'activity' => ['table' => 'as_schedule_activities', 'label' => 'Activity', 'group' => 'board', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['activityTitle'], 'sub' => 'targetDate', 'parent' => ['as_schedule_activity_versions', 'versionId']],
        'irrigation' => ['table' => 'as_schedule_irrigations', 'label' => 'Irrigation', 'group' => 'board', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['irrigationTitle']],
        'rule' => ['table' => 'as_schedule_critical_rules', 'label' => 'Critical rule', 'group' => 'board', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['ruleText']],
        'lot' => ['table' => 'as_schedule_lots', 'label' => 'Lot', 'group' => 'lots', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['lotName'], 'sub' => 'lotSize'],
        'fieldlot' => ['table' => 'as_field_profiles', 'label' => 'Farm lot', 'group' => 'lots', 'owner' => ['user', 'userId'], 'title' => ['name'], 'sub' => 'town'],
        'note' => ['table' => 'as_schedule_notes', 'label' => 'Note', 'group' => 'notes', 'owner' => ['season', 'croppingScheduleId'], 'user' => 'userId', 'title' => ['title', 'body']],
        'daynote' => ['table' => 'as_schedule_date_notes', 'label' => 'Day note', 'group' => 'notes', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['noteContent'], 'sub' => 'noteDate'],
        'boardnote' => ['table' => 'as_inline_notes', 'label' => 'Board note', 'group' => 'notes', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['title', 'content'], 'sub' => 'noteDate'],
        'doc' => ['table' => 'as_schedule_doc_entries', 'label' => 'Documentation', 'group' => 'notes', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['title', 'content']],
        'inventory' => ['table' => 'as_inventory_items', 'label' => 'Inventory item', 'group' => 'supplies', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['name']],
        'material' => ['table' => 'as_schedule_materials', 'label' => 'Material', 'group' => 'supplies', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['materialName']],
        'expense' => ['table' => 'as_schedule_day_expenses', 'label' => 'Expense', 'group' => 'money', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['note'], 'sub' => 'expenseDate', 'amount' => 'amount', 'skip' => 'sourced'],
        'income' => ['table' => 'as_schedule_day_incomes', 'label' => 'Income', 'group' => 'money', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['title', 'note'], 'sub' => 'incomeDate', 'amount' => 'amount', 'skip' => 'sourced'],
        'harvest' => ['table' => 'as_schedule_post_harvests', 'label' => 'Harvest record', 'group' => 'money', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['title'], 'sub' => 'observationDate'],
        'album' => ['table' => 'as_gallery_albums', 'label' => 'Album', 'group' => 'photos', 'owner' => ['season', 'croppingScheduleId'], 'user' => 'userId', 'title' => ['title']],
        'photo' => ['table' => 'as_gallery_images', 'label' => 'Photo', 'group' => 'photos', 'owner' => ['season', 'croppingScheduleId'], 'user' => 'userId', 'title' => ['caption'], 'parent' => ['as_gallery_albums', 'albumId']],
        'map' => ['table' => 'as_schedule_map_saves', 'label' => 'Saved map', 'group' => 'maps', 'owner' => ['user', 'userId'], 'title' => ['title']],
        'worker' => ['table' => 'as_schedule_workers', 'label' => 'Season worker', 'group' => 'people', 'owner' => ['season', 'croppingScheduleId'], 'title' => ['workerName']],
        'globalworker' => ['table' => 'as_global_workers', 'label' => 'Worker', 'group' => 'people', 'owner' => ['user', 'userId'], 'title' => ['workerName']],
        'contact' => ['table' => 'as_contacts', 'label' => 'Contact', 'group' => 'people', 'owner' => ['user', 'userId'], 'title' => ['name'], 'sub' => 'company'],
        'tag' => ['table' => 'as_schedule_tags', 'label' => 'Tag', 'group' => 'other', 'owner' => ['season', 'croppingScheduleId'], 'user' => 'userId', 'title' => ['name']],
        'analysis' => ['table' => 'as_plant_analyses', 'label' => 'Saved analysis', 'group' => 'tools', 'owner' => ['user', 'userId'], 'title' => ['title'], 'sub' => 'kind', 'skip' => 'failed'],
        'protocol' => ['table' => 'as_protocols', 'label' => 'Protocol', 'group' => 'tools', 'owner' => ['user', 'userId'], 'title' => ['title']],
        'npk' => ['table' => 'as_npk_calcs', 'label' => 'NPK Plus', 'group' => 'tools', 'owner' => ['user', 'userId'], 'title' => ['title']],
        'report' => ['table' => 'as_farm_reports', 'label' => 'Farm report', 'group' => 'tools', 'owner' => ['user', 'userId'], 'title' => ['title'], 'skip' => 'failed'],
        'chat' => ['table' => 'anisystem_ai_conversations', 'label' => 'Chat with Anee', 'group' => 'chats', 'owner' => ['user', 'userId'], 'title' => ['title']],
    ];

    /** The tool a saved analysis came from (as_plant_analyses.kind), shown as its label. */
    private const TOOLS = ['when' => 'When to Plant', 'what' => 'What to Plant', 'variety' => 'Variety analysis', 'protocol' => 'Crop Protocol', 'landprep' => 'Land Prep',
        'lcc' => 'LCC Checker', 'bilad' => 'Benta o Bilad', 'hatian' => 'Hatian Calculator', 'seedcalc' => 'Seed Count and Distance', 'compat' => 'Compatibility Checker', 'satellite' => 'Satellite Analysis',
        'skyweather' => 'Satellite Weather', 'npk' => 'NPK Plus', 'npkplan' => 'NPK Plus', 'npkproto' => 'NPK Plus'];

    public const GROUPS = ['seasons' => 'Seasons', 'board' => 'Board', 'lots' => 'Lots', 'notes' => 'Notes', 'photos' => 'Photos', 'supplies' => 'Inventory', 'money' => 'Money and harvest',
        'people' => 'People', 'tools' => 'Tools and analyses', 'maps' => 'Maps', 'chats' => 'Chats', 'other' => 'Other'];

    /** The kind of a table, or null when its deletes are not the Trash's business. */
    public static function kindOf(string $table): ?string
    {
        static $by = null;
        $by ??= array_combine(array_column(self::KINDS, 'table'), array_keys(self::KINDS));

        return $by[$table] ?? null;
    }

    public static function ready(): bool
    {
        static $yes = null;

        return $yes ??= Schema::hasTable('as_trash');
    }

    /* ============================================================ the book */

    /**
     * A model was saved with a new deleteStatus (BaseModel's update event):
     * 0 files it in the Trash now, by whoever is signed in; 1 takes it out.
     */
    public static function changed(object $model): void
    {
        try {
            $table = $model->getTable();
            $kind = self::kindOf($table);
            if (! $kind || ! self::ready()) {
                return;
            }
            if ((int) $model->deleteStatus === 0) {
                self::record($kind, (object) $model->getAttributes(), now('Asia/Manila'), Auth::id() ? (int) Auth::id() : null);
            } else {
                DB::table('as_trash')->where('tableName', $table)->where('rowId', (int) $model->getKey())->delete();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** File one deleted row. */
    private static function record(string $kind, object $row, Carbon $at, ?int $by, ?int $owner = null, ?int $scheduleId = null): void
    {
        $k = self::KINDS[$kind];
        if (match ($k['skip'] ?? null) { 'failed' => ($row->status ?? null) === 'failed', 'sourced' => trim((string) ($row->sourceRef ?? '')) !== '', default => false }) {
            return;
        }
        [$how, $col] = $k['owner'];
        if ($how === 'season') {
            $scheduleId = (int) ($row->{$col} ?? 0) ?: null;
            $owner ??= $scheduleId ? (int) DB::table('as_cropping_schedules')->where('id', $scheduleId)->value('anisystemUserId') : null;
            $owner = $owner ?: (! empty($k['user']) ? ((int) ($row->{$k['user']} ?? 0) ?: null) : null);
        } else {
            $owner ??= (int) ($row->{$col} ?? 0) ?: null;
            if ($kind === 'season') {
                $scheduleId = (int) $row->id;
            }
        }
        if (! $owner) {
            return;
        }
        [$pt, $pc] = $k['parent'] ?? [null, null];
        DB::table('as_trash')->upsert([[
            'ownerId' => $owner, 'kind' => $kind, 'tableName' => $k['table'], 'rowId' => (int) $row->id,
            'title' => mb_substr(self::titleOf($kind, $row), 0, 255), 'subtitle' => mb_substr(self::subOf($kind, $row), 0, 255) ?: null,
            'scheduleId' => $scheduleId, 'parentTable' => $pc && ! empty($row->{$pc}) ? $pt : null, 'parentId' => $pc && ! empty($row->{$pc}) ? (int) $row->{$pc} : null,
            'deletedBy' => $by, 'deletedAt' => $at->format('Y-m-d H:i:s'), 'state' => 'trash',
            'created_at' => now('Asia/Manila'), 'updated_at' => now('Asia/Manila'),
        ]], ['tableName', 'rowId'], ['ownerId', 'kind', 'title', 'subtitle', 'scheduleId', 'parentTable', 'parentId', 'deletedBy', 'deletedAt', 'state', 'updated_at']);
    }

    private static function titleOf(string $kind, object $row): string
    {
        foreach (self::KINDS[$kind]['title'] as $c) {
            $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($row->{$c} ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($t !== '') {
                return \Illuminate\Support\Str::limit($t, 120);
            }
        }

        return match ($kind) {
            'photo' => 'A photo', 'expense' => 'An expense', 'income' => 'An income', 'daynote', 'boardnote', 'note' => 'A note', 'chat' => 'A chat with Anee', default => 'Untitled',
        };
    }

    private static function subOf(string $kind, object $row): string
    {
        $k = self::KINDS[$kind];
        $bits = [];
        if (! empty($k['amount']) && isset($row->{$k['amount']})) {
            $bits[] = Region::priceTag((float) $row->{$k['amount']});
        }
        $sub = $k['sub'] ?? null;
        if ($sub && trim((string) ($row->{$sub} ?? '')) !== '') {
            $v = (string) $row->{$sub};
            $bits[] = match (true) {
                $kind === 'lot' => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.') . ' ' . ($row->lotSizeUnit ?? 'hectare'),
                $kind === 'analysis' => self::TOOLS[$v] ?? ucfirst(str_replace('_', ' ', $v)),
                (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $v) => Carbon::parse($v)->format('M j, Y'),
                default => $v,
            };
        }
        if ($kind === 'photo' && ! empty($row->path)) {
            $bits[] = 'path:' . $row->path;
        }

        return implode(' · ', $bits);
    }

    /* ============================================================ the sweep */

    /**
     * The deletes no model event saw (mass updates), filed; the ones put back
     * elsewhere, taken out. For one owner (the Trash page opening) or all.
     * One query each way across every kind.
     */
    public static function sweep(?int $owner = null): int
    {
        if (! self::ready()) {
            return 0;
        }
        $found = 0;
        // Put back elsewhere, or gone: out of the book.
        $gone = [];
        foreach (self::KINDS as $kind => $k) {
            $q = DB::table('as_trash as t')->leftJoin($k['table'] . ' as r', 'r.id', '=', 't.rowId')
                ->where('t.tableName', $k['table'])->where(fn ($w) => $w->whereNull('r.id')->orWhere('r.deleteStatus', '!=', 0))
                ->when($owner, fn ($w) => $w->where('t.ownerId', $owner))->select('t.id');
            $gone[] = $q;
        }
        $ids = self::union($gone)->pluck('id')->all();
        if ($ids) {
            DB::table('as_trash')->whereIn('id', $ids)->delete();
        }
        // Deleted and not yet in the book.
        $new = [];
        foreach (self::KINDS as $kind => $k) {
            [$how, $col] = $k['owner'];
            $q = DB::table($k['table'] . ' as r')
                ->leftJoin('as_trash as t', fn ($j) => $j->on('t.rowId', '=', 'r.id')->where('t.tableName', '=', $k['table']))
                ->whereNull('t.id')->where('r.deleteStatus', 0);
            match ($k['skip'] ?? null) {
                'failed' => $q->where(fn ($w) => $w->whereNull('r.status')->orWhere('r.status', '!=', 'failed')),
                'sourced' => $q->where(fn ($w) => $w->whereNull('r.sourceRef')->orWhere('r.sourceRef', '')),
                default => null,
            };
            if ($how === 'season') {
                $who = ! empty($k['user']) ? 'COALESCE(s.anisystemUserId, r.' . $k['user'] . ')' : 's.anisystemUserId';
                $q->leftJoin('as_cropping_schedules as s', 's.id', '=', 'r.' . $col)->whereRaw($who . ' > 0')->selectRaw('? as kind, r.id as id, ' . $who . ' as ownerId', [$kind]);
                if ($owner) {
                    $q->whereRaw($who . ' = ?', [$owner]);
                }
            } else {
                $q->selectRaw('? as kind, r.id as id, r.' . $col . ' as ownerId', [$kind]);
                if ($owner) {
                    $q->where('r.' . $col, $owner);
                }
            }
            $new[] = $q->limit(500);
        }
        $rows = self::union($new)->get()->groupBy('kind');
        $now = now('Asia/Manila');
        foreach ($rows as $kind => $list) {
            $k = self::KINDS[$kind];
            foreach (DB::table($k['table'])->whereIn('id', $list->pluck('id')->all())->get() as $r) {
                // A mass update stamps updated_at; a raw one does not, and then now is the best we know.
                $at = ! empty($r->updated_at) && Carbon::parse($r->updated_at, 'Asia/Manila')->gt($now->copy()->subDay()) ? Carbon::parse($r->updated_at, 'Asia/Manila') : $now->copy();
                self::record($kind, $r, $at, null, (int) ($list->firstWhere('id', $r->id)->ownerId ?? 0) ?: null);
                $found++;
            }
        }

        return $found;
    }

    /** The owner's sweep, at most once a minute (the Trash page and its badge ask often). */
    public static function sweepSoon(int $owner): void
    {
        if (Cache::add('trash:swept:' . $owner, 1, 60)) {
            try {
                self::sweep($owner);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private static function union(array $queries)
    {
        $first = array_shift($queries);
        foreach ($queries as $q) {
            $first->unionAll($q);
        }

        return $first;
    }

    /** What was already deleted the day the Trash came, filed as legacy: never shown, never emptied. */
    public static function fileLegacy(): void
    {
        $now = now('Asia/Manila')->format('Y-m-d H:i:s');
        foreach (self::KINDS as $kind => $k) {
            if (! Schema::hasTable($k['table'])) {
                continue;
            }
            [$how, $col] = $k['owner'];
            $q = DB::table($k['table'] . ' as r')->where('r.deleteStatus', 0)
                ->leftJoin('as_trash as t', fn ($j) => $j->on('t.rowId', '=', 'r.id')->where('t.tableName', '=', $k['table']))->whereNull('t.id');
            if ($how === 'season') {
                $q->leftJoin('as_cropping_schedules as s', 's.id', '=', 'r.' . $col)
                    ->selectRaw('COALESCE(s.anisystemUserId, ' . (! empty($k['user']) ? 'r.' . $k['user'] . ', ' : '') . '0) as ownerId, ? as kind, ? as tableName, r.id as rowId, r.' . $col . ' as scheduleId, COALESCE(r.updated_at, ?) as deletedAt, ? as state, ? as created_at, ? as updated_at',
                        [$kind, $k['table'], $now, 'legacy', $now, $now]);
            } else {
                $q->selectRaw('COALESCE(r.' . $col . ', 0) as ownerId, ? as kind, ? as tableName, r.id as rowId, NULL as scheduleId, COALESCE(r.updated_at, ?) as deletedAt, ? as state, ? as created_at, ? as updated_at',
                    [$kind, $k['table'], $now, 'legacy', $now, $now]);
            }
            DB::table('as_trash')->insertUsing(['ownerId', 'kind', 'tableName', 'rowId', 'scheduleId', 'deletedAt', 'state', 'created_at', 'updated_at'], $q);
        }
    }

    /* ============================================================ reading */

    /**
     * The owner's Trash: what is in it, newest first, each with the days it
     * has left; what was deleted with a parent that is also here rides inside
     * the parent's row (`with`).
     */
    public static function listFor(int $owner): array
    {
        $rows = DB::table('as_trash')->where('ownerId', $owner)->where('state', 'trash')->orderByDesc('deletedAt')->orderByDesc('id')->get();
        $byRow = [];
        foreach ($rows as $r) {
            $byRow[$r->tableName . ':' . $r->rowId] = $r;
        }
        $seasonRow = [];
        foreach ($rows as $r) {
            if ($r->kind === 'season') {
                $seasonRow[(int) $r->rowId] = $r;
            }
        }
        $top = [];
        $with = [];
        foreach ($rows as $r) {
            $host = self::hostOf($r, $byRow, $seasonRow);
            if ($host) {
                $with[$host->id][] = $r;
            } else {
                $top[] = $r;
            }
        }
        $seasonIds = $rows->pluck('scheduleId')->filter()->unique()->values()->all();
        $seasons = $seasonIds ? DB::table('as_cropping_schedules')->whereIn('id', $seasonIds)->get(['id', 'title', 'deleteStatus'])->keyBy('id') : collect();
        $byIds = $rows->pluck('deletedBy')->filter()->unique()->values()->all();
        $people = $byIds ? \App\Models\User::whereIn('id', $byIds)->get()->mapWithKeys(fn ($u) => [$u->id => trim((string) ($u->full_name ?? '')) ?: ($u->email ?? '')]) : collect();
        $now = now('Asia/Manila');

        return array_map(function ($r) use ($with, $seasons, $people, $now, $owner) {
            $left = max(0, self::DAYS - (int) floor(Carbon::parse($r->deletedAt, 'Asia/Manila')->diffInHours($now) / 24));
            $season = $r->scheduleId && $r->kind !== 'season' ? $seasons->get((int) $r->scheduleId) : null;
            $kids = $with[$r->id] ?? [];
            $counts = [];
            foreach ($kids as $kid) {
                $counts[$kid->kind] = ($counts[$kid->kind] ?? 0) + 1;
            }
            [$sub, $thumb] = self::splitThumb((string) $r->subtitle);

            // A saved analysis wears its tool's name.
            $label = self::KINDS[$r->kind]['label'] ?? $r->kind;
            if ($r->kind === 'analysis' && in_array($sub, self::TOOLS, true)) {
                [$label, $sub] = [$sub, ''];
            }

            return [
                'id' => (int) $r->id, 'kind' => $r->kind, 'label' => $label, 'group' => self::KINDS[$r->kind]['group'] ?? 'other',
                'title' => (string) $r->title, 'sub' => $sub, 'thumb' => $thumb,
                'season' => $season ? ['title' => $season->title, 'inTrash' => (int) $season->deleteStatus === 0] : null,
                'by' => $r->deletedBy && (int) $r->deletedBy !== $owner ? ($people[(int) $r->deletedBy] ?? 'A worker') : null,
                'at' => Carbon::parse($r->deletedAt, 'Asia/Manila')->toIso8601String(), 'day' => Carbon::parse($r->deletedAt, 'Asia/Manila')->toDateString(),
                'left' => $left,
                'with' => array_map(fn ($k, $n) => ['kind' => $k, 'label' => self::KINDS[$k]['label'] ?? $k, 'n' => $n], array_keys($counts), $counts),
            ];
        }, $top);
    }

    /** The trash row this one was deleted with (its season, album or board version), or null. */
    private static function hostOf(object $r, array $byRow, array $seasonRow): ?object
    {
        $near = fn (object $a, object $b) => abs(Carbon::parse($a->deletedAt)->diffInSeconds(Carbon::parse($b->deletedAt))) <= self::WITH;
        if ($r->parentTable && ($p = $byRow[$r->parentTable . ':' . $r->parentId] ?? null) && $near($r, $p)) {
            return self::hostOf($p, $byRow, $seasonRow) ?? $p;
        }
        if ($r->kind !== 'season' && $r->scheduleId && ($s = $seasonRow[(int) $r->scheduleId] ?? null) && $near($r, $s)) {
            return $s;
        }

        return null;
    }

    private static function splitThumb(string $sub): array
    {
        if (preg_match('/(?:^| · )path:(.+)$/', $sub, $m)) {
            return [trim(preg_replace('/(?:^| · )path:.+$/', '', $sub)), MediaStore::url($m[1])];
        }

        return [$sub, null];
    }

    public static function countFor(int $owner): int
    {
        return self::ready() ? (int) DB::table('as_trash')->where('ownerId', $owner)->where('state', 'trash')->count() : 0;
    }

    /* ============================================================ restore */

    /**
     * Put a thing back, and what was deleted with it. Returns [ok, message,
     * where to open it]. Refused while what it belongs to is still in the
     * Trash, or when the plan has no room for it.
     */
    public static function restore(int $owner, int $id): array
    {
        // What a mass update deleted with it (an album's photos) is filed first, so it comes back too.
        self::sweep($owner);
        $e = DB::table('as_trash')->where('id', $id)->where('ownerId', $owner)->where('state', 'trash')->first();
        if (! $e) {
            return [false, 'That is no longer in the Trash.', null];
        }
        $k = self::KINDS[$e->kind] ?? null;
        $row = $k ? DB::table($k['table'])->where('id', $e->rowId)->first() : null;
        if (! $row) {
            DB::table('as_trash')->where('id', $e->id)->delete();

            return [false, 'That one is gone for good.', null];
        }
        if ($e->kind !== 'season' && $e->scheduleId && (int) DB::table('as_cropping_schedules')->where('id', $e->scheduleId)->value('deleteStatus') === 0) {
            return [false, 'Its season "' . DB::table('as_cropping_schedules')->where('id', $e->scheduleId)->value('title') . '" is in the Trash. Restore the season first.', null];
        }
        if ($e->parentTable && (int) DB::table($e->parentTable)->where('id', $e->parentId)->value('deleteStatus') === 0) {
            $pk = self::kindOf($e->parentTable);

            return [false, 'Its ' . mb_strtolower(self::KINDS[$pk]['label'] ?? 'parent') . ' is in the Trash. Restore that first.', null];
        }
        if ($e->kind === 'season') {
            $user = \App\Models\User::find($owner);
            if ($user && ! $user->canCreateSchedule()) {
                $limit = $user->scheduleLimit();

                return [false, 'Your plan allows ' . ($limit === 0 ? 'no' : 'up to ' . $limit) . ' active ' . ($limit === 1 ? 'season' : 'seasons') . '. Finish or archive one, then restore this one.', null];
            }
        }
        // What was deleted with it comes back too.
        $all = DB::table('as_trash')->where('ownerId', $owner)->where('state', 'trash')->get();
        $byRow = [];
        $seasonRow = [];
        foreach ($all as $r) {
            $byRow[$r->tableName . ':' . $r->rowId] = $r;
            if ($r->kind === 'season') {
                $seasonRow[(int) $r->rowId] = $r;
            }
        }
        $back = [$e];
        foreach ($all as $r) {
            if ($r->id !== $e->id && ($h = self::hostOf($r, $byRow, $seasonRow)) && (int) $h->id === (int) $e->id) {
                $back[] = $r;
            }
        }
        DB::transaction(function () use ($back) {
            foreach ($back as $r) {
                DB::table($r->tableName)->where('id', $r->rowId)->update(['deleteStatus' => 1, 'updated_at' => now('Asia/Manila')]);
            }
            DB::table('as_trash')->whereIn('id', array_map(fn ($r) => $r->id, $back))->delete();
        });
        $n = count($back) - 1;

        $name = $e->kind === 'analysis' ? (self::TOOLS[$row->kind ?? ''] ?? $k['label']) : ($k['label'] ?? 'It');

        return [true, $name . ' restored' . ($n ? ', with ' . $n . ' ' . ($n === 1 ? 'thing' : 'things') . ' deleted with it' : '') . '.', self::openUrl($e->kind, $row)];
    }

    /** Where a restored thing is found again. */
    private static function openUrl(string $kind, object $row): ?string
    {
        $sid = (int) ($row->croppingScheduleId ?? 0);
        try {
            return match ($kind) {
                'season' => route('sm.hub', ['id' => $row->id]),
                'activity', 'version', 'irrigation', 'rule', 'daynote', 'boardnote', 'expense', 'income', 'harvest' => $sid ? route('sm.activities', ['id' => $sid]) : null,
                'note', 'doc' => $sid ? route('sm.notes', ['id' => $sid]) : route('notes.hub'),
                'album', 'photo' => $sid ? route('sm.gallery', ['id' => $sid]) : route('gallery.hub'),
                'inventory', 'material' => $sid ? route('sm.inventory', ['id' => $sid]) : null,
                'fieldlot' => route('farmlots.index'),
                'map' => route('maps.page', ['save' => $row->id]),
                'protocol' => route('pb.open', ['id' => $row->id]),
                'npk' => route('npk.page'),
                'chat' => route('ai.index'),
                'contact' => route('contacts.page'),
                'globalworker' => route('workers.global'),
                default => $sid ? route('sm.hub', ['id' => $sid]) : null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    /* ============================================================ for good */

    /** Delete one thing for good, with everything that hangs on it. */
    public static function purge(int $owner, int $id): array
    {
        $e = DB::table('as_trash')->where('id', $id)->where('ownerId', $owner)->where('state', 'trash')->first();
        if (! $e) {
            return [false, 'That is no longer in the Trash.'];
        }
        self::purgeEntry($e);

        return [true, (self::KINDS[$e->kind]['label'] ?? 'It') . ' deleted for good.'];
    }

    /** Everything in the owner's Trash, for good. */
    public static function empty(int $owner): int
    {
        $n = 0;
        // Seasons first: they take their own things with them.
        while ($e = DB::table('as_trash')->where('ownerId', $owner)->where('state', 'trash')->orderByRaw("kind = 'season' desc")->orderBy('id')->first()) {
            self::purgeEntry($e);
            $n++;
            if ($n > 5000) {
                break;
            }
        }

        return $n;
    }

    /** What has been in the Trash for 30 days, for good. Returns how many. */
    public static function purgeDue(): int
    {
        if (! self::ready()) {
            return 0;
        }
        $n = 0;
        $before = now('Asia/Manila')->subDays(self::DAYS)->format('Y-m-d H:i:s');
        while ($e = DB::table('as_trash')->where('state', 'trash')->where('deletedAt', '<', $before)->orderByRaw("kind = 'season' desc")->orderBy('id')->first()) {
            self::purgeEntry($e);
            if (++$n > 5000) {
                break;
            }
        }

        return $n;
    }

    private static function purgeEntry(object $e): void
    {
        $files = [];
        $k = self::KINDS[$e->kind] ?? null;
        DB::transaction(function () use ($e, $k, &$files) {
            $row = $k ? DB::table($k['table'])->where('id', $e->rowId)->first() : null;
            // Only a row that is still deleted: one put back since is not taken.
            if ($row && (int) $row->deleteStatus === 0) {
                $files = self::cascade($e->kind, $row);
                DB::table($k['table'])->where('id', $row->id)->delete();
            }
            DB::table('as_trash')->where('id', $e->id)->delete();
            // The entries of rows the cascade took.
            if ($e->kind === 'season') {
                DB::table('as_trash')->where('scheduleId', $e->rowId)->where('kind', '!=', 'season')
                    ->whereNotIn('kind', ['fieldlot', 'map', 'chat', 'analysis', 'protocol', 'npk', 'contact', 'globalworker'])->delete();
            } else {
                DB::table('as_trash')->where('parentTable', $e->tableName)->where('parentId', $e->rowId)->delete();
            }
        });
        foreach (array_unique(array_filter($files)) as $f) {
            try {
                // A copied season or protocol can point at the same stored file: it stays while anything still names it.
                if (! self::fileInUse($f)) {
                    MediaStore::delete($f);
                }
            } catch (\Throwable $ex) {
                report($ex);
            }
        }
    }

    /**
     * What hangs on a row deleted for good, deleted with it (or let go, where
     * it is the farmer's own beyond this thing: a farm lot, a map, a chat).
     * Returns the stored files to remove after the rows are gone.
     */
    private static function cascade(string $kind, object $row): array
    {
        $files = [];
        switch ($kind) {
            case 'season':
                $sid = (int) $row->id;
                $files = DB::table('as_gallery_images')->where('croppingScheduleId', $sid)->pluck('path')->all();
                self::dropActivities(DB::table('as_schedule_activities')->where('croppingScheduleId', $sid)->pluck('id')->all());
                // The farmer's own things that only remembered the season let it go; a saved map is everyone's at 0.
                DB::table('as_field_profiles')->where('scheduleId', $sid)->update(['scheduleId' => null]);
                DB::table('anisystem_ai_conversations')->where('croppingScheduleId', $sid)->update(['croppingScheduleId' => null]);
                DB::table('as_schedule_map_saves')->where('scheduleId', $sid)->update(['scheduleId' => 0]);
                // Community posts about it and the mail already sent are not the season's to take.
                $keep = ['as_field_profiles', 'anisystem_ai_conversations', 'as_schedule_map_saves', 'as_community_comments', 'as_community_ratings', 'as_email_tasks', 'as_cropping_schedules', 'as_trash'];
                foreach (self::seasonTables() as [$t, $c]) {
                    if (! in_array($t, $keep, true)) {
                        DB::table($t)->where($c, $sid)->delete();
                    }
                }
                break;
            case 'version':
                self::dropActivities(DB::table('as_schedule_activities')->where('versionId', $row->id)->pluck('id')->all());
                foreach (['as_schedule_date_notes', 'as_inline_notes', 'as_schedule_day_expenses', 'as_schedule_day_incomes'] as $t) {
                    DB::table($t)->where('versionId', $row->id)->delete();
                }
                break;
            case 'activity':
                self::dropActivities([(int) $row->id], false);
                break;
            case 'lot':
                DB::table('as_schedule_activity_lots')->where('lotId', $row->id)->delete();
                foreach (['as_schedule_date_notes', 'as_inline_notes', 'as_schedule_post_harvests', 'as_schedule_calendar_events'] as $t) {
                    DB::table($t)->where('lotId', $row->id)->update(['lotId' => null]);
                }
                DB::table('as_field_profiles')->where('lotId', $row->id)->update(['lotId' => null]);
                break;
            case 'inventory':
                DB::table('as_inventory_moves')->where('itemId', $row->id)->delete();
                DB::table('as_schedule_activity_items')->where('inventoryItemId', $row->id)->update(['inventoryItemId' => null]);
                break;
            case 'material':
                DB::table('as_schedule_activity_items')->where('materialId', $row->id)->update(['materialId' => null]);
                break;
            case 'worker':
                DB::table('as_schedule_activity_workers')->where('workerId', $row->id)->delete();
                break;
            case 'album':
                $files = DB::table('as_gallery_images')->where('albumId', $row->id)->where('deleteStatus', 0)->pluck('path')->all();
                DB::table('as_gallery_images')->where('albumId', $row->id)->where('deleteStatus', 0)->delete();
                DB::table('as_gallery_images')->where('albumId', $row->id)->update(['albumId' => null]);
                break;
            case 'photo':
                $files = [(string) $row->path];
                break;
            case 'protocol':
                DB::table('as_protocol_versions')->where('protocolId', $row->id)->delete();
                DB::table('as_protocol_analyses')->where('protocolId', $row->id)->delete();
                break;
            case 'chat':
                DB::table('anisystem_ai_messages')->where('conversationId', $row->id)->delete();
                break;
            case 'globalworker':
                DB::table('as_schedule_workers')->where('globalWorkerId', $row->id)->update(['globalWorkerId' => null]);
                break;
        }

        return $files;
    }

    /** Activities for good: their item lines, lot and worker links and calendar events; the notes and moves they had are let go. */
    private static function dropActivities(array $ids, bool $rows = true): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (['as_schedule_activity_items', 'as_schedule_activity_lots', 'as_schedule_activity_workers', 'as_schedule_calendar_events'] as $t) {
                DB::table($t)->whereIn('activityId', $chunk)->delete();
            }
            foreach (['as_inline_notes', 'as_schedule_date_notes', 'as_inventory_moves'] as $t) {
                DB::table($t)->whereIn('activityId', $chunk)->update(['activityId' => null]);
            }
            DB::table('anisystem_ai_conversations')->whereIn('linkedActivityId', $chunk)->update(['linkedActivityId' => null]);
            if ($rows) {
                DB::table('as_schedule_activities')->whereIn('id', $chunk)->delete();
            }
        }
    }

    private static function fileInUse(string $path): bool
    {
        return DB::table('as_gallery_images')->where('path', $path)->exists()
            || DB::table('as_protocol_versions')->where('files', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], basename($path)) . '%')->exists();
    }

    /** Every table of this app that hangs on a season, with its column. */
    private static function seasonTables(): array
    {
        return Cache::remember('trash:season-tables', 3600, function () {
            return collect(DB::select("SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME IN ('croppingScheduleId', 'scheduleId') AND (TABLE_NAME LIKE 'as\\_%' OR TABLE_NAME LIKE 'anisystem\\_%')"))
                ->map(fn ($r) => [$r->t, $r->c])->values()->all();
        });
    }
}
