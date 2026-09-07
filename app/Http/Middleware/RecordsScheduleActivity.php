<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The season's diary of hands: one line per successful write.
 *
 * Not ninety calls to a logger — the schedule resolver stashes the season id
 * on the request, and this middleware writes the line after the response is
 * known good. Every module inherits the diary the day it is written, and a
 * module that never resolves a schedule never lands in the wrong book.
 */
class RecordsScheduleActivity
{
    /**
     * Route names said plainly. Anything not here is humanized from its
     * route name, so a new endpoint is legible before it is translated.
     */
    private const SAYS = [
        'sm.update' => 'Updated the schedule settings',
        'sm.day-type' => 'Changed how days are counted',
        'sm.status' => 'Changed the season status',
        'sm.activities.store' => 'Added an activity',
        'sm.activities.update' => 'Edited an activity',
        'sm.activities.destroy' => 'Deleted an activity',
        'sm.activities.toggle-done' => 'Ticked an activity',
        'sm.activities.day-expense.save' => 'Recorded a day expense',
        'sm.activities.day-expense.delete' => 'Removed a day expense',
        'sm.activities.day-income.save' => 'Recorded a day income',
        'sm.activities.day-income.delete' => 'Removed a day income',
        'sm.lots.store' => 'Added a lot',
        'sm.lots.update' => 'Edited a lot',
        'sm.lots.destroy' => 'Deleted a lot',
        'sm.workers.store' => 'Added a worker',
        'sm.workers.update' => 'Edited a worker',
        'sm.workers.destroy' => 'Deleted a worker',
        'sm.workers.access.grant' => 'Sent a worker login invite',
        'sm.workers.access.password' => 'Created a worker login',
        'sm.workers.access.revoke' => 'Revoked a worker login',
        'sm.inventory.store' => 'Added an inventory item',
        'sm.inventory.update' => 'Edited an inventory item',
        'sm.inventory.destroy' => 'Deleted an inventory item',
        'sm.inventory.move' => 'Moved inventory stock',
        'sm.inventory.move.delete' => 'Removed a stock entry',
        'sm.inventory.move.update' => 'Corrected a stock entry',
        'sm.notes.store' => 'Wrote a note',
        'sm.notes.update' => 'Edited a note',
        'sm.notes.destroy' => 'Deleted a note',
        'sm.notes.image-upload' => 'Attached a photo to a note',
        'sm.notes.video-upload' => 'Attached a video to a note',
        'sm.notes.audio-upload' => 'Attached a voice note',
        'quick-voice.clip' => 'Filed a voice note',
        'quick-record.clip' => 'Filed a video clip',
        'sm.doc-entries.store' => 'Added a document',
        'sm.doc-entries.update' => 'Edited a document',
        'sm.doc-entries.destroy' => 'Deleted a document',
        'sm.post-harvest.store' => 'Recorded an observation',
        'sm.post-harvest.update' => 'Edited an observation',
        'sm.post-harvest.destroy' => 'Deleted an observation',
        'sm.map.save' => 'Saved a map',
        'sm.map.save.meta' => 'Renamed a saved map',
        'sm.draw.save' => 'Saved a drawing',
        'sm.tags.store' => 'Coined a tag',
        'sm.tags.destroy' => 'Deleted a tag',
        'sm.anee.generate' => 'Ran an Anee report',
        'sm.protocol.generate' => 'Wrote a protocol report',
        'sm.compare.generate' => 'Compared two reports',
        'sm.report.snapshot' => 'Saved a report snapshot',
        'sm.gallery.image.store' => 'Added a picture to the gallery',
        'quick-capture.notes' => 'Filed quick-capture photos',
        'quick-capture.gallery' => 'Filed photos to an album',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Photographed before the hand moves, so an edit can say what it
        // changed FROM — the response only ever knows the after.
        $before = null;
        if (! $request->isMethodSafe() && Auth::check()) {
            try {
                $before = $this->snapBefore($request);
            } catch (\Throwable $e) {
                // No photograph is a poorer diary line, not a broken page.
            }
        }

        $response = $next($request);

        try {
            $this->record($request, $response, $before);
        } catch (\Throwable $e) {
            // The diary must never break the hand that writes it.
        }

        return $response;
    }

    /**
     * Routes whose target row is worth photographing BEFORE the change, so
     * an edit can say from and to: route name => [model, whitelisted fields].
     * The id comes from ?id= — the convention every one of these follows.
     */
    private const SNAP = [
        'sm.activities.update' => [\App\Models\AsScheduleActivity::class, ['activityTitle', 'targetDate', 'activityType', 'priority', 'timeRequired', 'isDone', 'isHidden', 'isDraft']],
        'sm.activities.destroy' => [\App\Models\AsScheduleActivity::class, ['activityTitle', 'targetDate', 'activityType']],
        'sm.activities.toggle-done' => [\App\Models\AsScheduleActivity::class, ['activityTitle', 'isDone']],
        'sm.lots.update' => [\App\Models\AsScheduleLot::class, ['lotName', 'lotSize', 'lotSizeUnit', 'variety']],
        'sm.lots.destroy' => [\App\Models\AsScheduleLot::class, ['lotName', 'lotSize', 'lotSizeUnit']],
        'sm.workers.update' => [\App\Models\AsScheduleWorker::class, ['workerName', 'email', 'phone', 'costPerHalfDay']],
        'sm.workers.destroy' => [\App\Models\AsScheduleWorker::class, ['workerName']],
        'sm.inventory.update' => [\App\Models\AsInventoryItem::class, ['name', 'kind', 'unit', 'lowAt', 'unitPrice']],
        'sm.inventory.destroy' => [\App\Models\AsInventoryItem::class, ['name', 'kind']],
        'sm.notes.update' => [\App\Models\AsScheduleNote::class, ['title']],
        'sm.notes.destroy' => [\App\Models\AsScheduleNote::class, ['title']],
        'sm.doc-entries.update' => [\App\Models\AsScheduleDocEntry::class, ['title', 'type']],
        'sm.doc-entries.destroy' => [\App\Models\AsScheduleDocEntry::class, ['title', 'type']],
        'sm.post-harvest.update' => [\App\Models\AsSchedulePostHarvest::class, ['title', 'category', 'observationDate', 'yieldAmount', 'yieldUnit', 'pricePerUnit', 'buyer']],
        'sm.post-harvest.destroy' => [\App\Models\AsSchedulePostHarvest::class, ['title']],
    ];

    /** Names that identify a row to a person, tried in this order. */
    private const NAMEISH = ['activityTitle', 'lotName', 'workerName', 'name', 'title', 'workerEmail'];

    /** Request keys the diary never repeats. */
    private const HUSH = ['password', 'token', '_token', 'body', 'content', 'notes', 'report', 'strokes', 'media', 'image', 'images', 'imagePaths', 'params', 'objects', 'keepPaths', 'clip', 'files', 'audio', 'video', 'description', 'details'];

    /** The row about to change, photographed for the from column. */
    private function snapBefore(Request $request): ?array
    {
        $name = (string) optional($request->route())->getName();
        $spec = self::SNAP[$name] ?? null;
        $id = (int) $request->query('id');
        if (! $spec || $id <= 0) {
            return null;
        }
        [$model, $fields] = $spec;
        try {
            $row = $model::query()->find($id);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $row) {
            return null;
        }
        $out = ['__id' => $id];
        foreach ($fields as $f) {
            $v = $row->{$f};
            $out[$f] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
        }

        return $out;
    }

    private function record(Request $request, Response $response, ?array $before = null): void
    {
        if ($request->isMethodSafe() || ! Auth::check()) {
            return;
        }
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return;             // only what actually happened
        }
        $scheduleId = (int) $request->attributes->get('auditScheduleId', 0);
        if ($scheduleId <= 0) {
            return;             // not a schedule's business
        }
        $name = (string) optional($request->route())->getName();
        if ($name === '') {
            return;
        }
        // Bookkeeping, not farming: the undo journal mirrors itself on every
        // step, and a diary that logs its own pen is unreadable.
        if (str_starts_with($name, 'sm.undo')) {
            return;
        }

        \App\Models\AsScheduleAudit::create([
            'croppingScheduleId' => $scheduleId,
            'userId' => (int) Auth::id(),
            'routeName' => Str::limit($name, 118, ''),
            'method' => $request->method(),
            'label' => self::SAYS[$name] ?? $this->humanize($name),
            'detail' => $this->detail($request, $response, $before, $name),
        ]);
    }

    /**
     * The particulars: which thing (entity), what changed field by field
     * (from → to, when the before was photographed), and what was sent
     * (sanitized, truncated). Null when there is nothing worth keeping.
     */
    private function detail(Request $request, Response $response, ?array $before, string $routeName): ?string
    {
        // The response's own reading of the row, when it is JSON with data.
        $after = [];
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $payload = $response->getData(true);
            $data = $payload['data'] ?? [];
            $after = is_array($data) ? ($data['data'] ?? $data) : [];
            if (! is_array($after)) {
                $after = [];
            }
        }

        // What the hand typed, minus secrets, prose and payload bulk.
        $input = [];
        foreach ($request->except(self::HUSH) as $k => $v) {
            if (! is_scalar($v) || $v === '' || str_contains(strtolower($k), 'password')) {
                continue;
            }
            $input[$k] = Str::limit((string) $v, 160);
            if (count($input) >= 25) {
                break;
            }
        }

        // From → to, on the fields the photograph kept.
        $changes = [];
        if ($before) {
            foreach ($before as $f => $was) {
                if ($f === '__id') {
                    continue;
                }
                $now = array_key_exists($f, $after) ? $after[$f]
                    : ($request->has($f) ? $request->input($f) : null);
                if ($now === null) {
                    continue;
                }
                if (is_bool($was) || is_bool($now)) {
                    $was = (bool) $was;
                    $now = (bool) $now;
                }
                if ((string) (is_bool($was) ? (int) $was : $was) !== (string) (is_bool($now) ? (int) $now : $now)) {
                    $changes[$f] = [
                        'from' => is_bool($was) ? ($was ? 'yes' : 'no') : Str::limit((string) $was, 120),
                        'to' => is_bool($now) ? ($now ? 'yes' : 'no') : Str::limit((string) $now, 120),
                    ];
                }
            }
        }

        // Which thing this was about, said by whatever name it answers to.
        $entityName = null;
        foreach (self::NAMEISH as $k) {
            $entityName = $entityName
                ?? ($before[$k] ?? null)
                ?? ($after[$k] ?? null)
                ?? ($input[$k] ?? null);
        }
        $entityId = (int) ($before['__id'] ?? ($after['id'] ?? 0)) ?: null;

        $detail = array_filter([
            'entity' => array_filter(['id' => $entityId, 'name' => $entityName !== null ? Str::limit((string) $entityName, 140) : null]),
            'changes' => $changes ?: null,
            'input' => $changes ? null : ($input ?: null),
        ]);

        if (! $detail) {
            return null;
        }

        $json = json_encode($detail, JSON_UNESCAPED_UNICODE);

        return strlen($json) > 60000 ? null : $json;
    }

    /** "sm.growth.note.save" -> "Growth note save" — legible until named. */
    private function humanize(string $name): string
    {
        $words = str_replace(['sm.', '.', '-'], ['', ' ', ' '], $name);

        return Str::ucfirst(trim($words));
    }
}
