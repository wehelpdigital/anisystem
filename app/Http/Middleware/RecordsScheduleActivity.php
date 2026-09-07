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
        $response = $next($request);

        try {
            $this->record($request, $response);
        } catch (\Throwable $e) {
            // The diary must never break the hand that writes it.
        }

        return $response;
    }

    private function record(Request $request, Response $response): void
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

        \App\Models\AsScheduleAudit::create([
            'croppingScheduleId' => $scheduleId,
            'userId' => (int) Auth::id(),
            'routeName' => Str::limit($name, 118, ''),
            'method' => $request->method(),
            'label' => self::SAYS[$name] ?? $this->humanize($name),
        ]);
    }

    /** "sm.growth.note.save" -> "Growth note save" — legible until named. */
    private function humanize(string $name): string
    {
        $words = str_replace(['sm.', '.', '-'], ['', ' ', ' '], $name);

        return Str::ucfirst(trim($words));
    }
}
