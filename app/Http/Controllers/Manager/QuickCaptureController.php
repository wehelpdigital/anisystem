<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\HtmlSanitizer;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Quick Capture — snap one or several photos, add an optional rich-text note,
 * and drop the group into a schedule's notebook in one go. (The "ask the AI
 * Technician about this photo" path is handled client-side via the existing
 * ai.photo + ai.ask endpoints, so it lands in the schedule's AI history.)
 */
class QuickCaptureController extends BaseScheduleController
{
    /** Save a captured photo group as notes on the chosen schedule. */
    public function storeNotes(Request $request)
    {
        $request->validate([
            'scheduleId' => 'required|integer',
            'note' => 'nullable|string|max:50000',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'images.required' => 'Capture at least one photo.',
            'images.*.max' => 'Each photo must be 8 MB or smaller.',
        ]);

        $schedule = $this->schedule($request->input('scheduleId'));

        $body = filled($request->input('note'))
            ? HtmlSanitizer::rich($request->input('note'))
            : null;

        $title = 'Quick capture — ' . Carbon::now()->format('M j, Y g:i A');
        $dir = 'schedule-notes/' . $schedule->id;

        $created = [];
        foreach ($request->file('images') as $file) {
            $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp']);
            $stem = Str::uuid()->toString();
            Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);
            $path = $dir . '/' . $stem . '.' . $ext;

            $created[] = AsScheduleNote::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'title' => $title,
                'body' => $body,
                'imagePath' => $path,
                'deleteStatus' => 1,
            ])->id;
        }

        $count = count($created);

        return $this->jsonOk(
            $count . ' ' . str('photo')->plural($count) . ' saved to notes.',
            ['count' => $count, 'notesUrl' => route('sm.notes', ['id' => $schedule->id])]
        );
    }
}
