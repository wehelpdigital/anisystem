<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleAttachment;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttachmentController extends BaseScheduleController
{
    /**
     * Upload one image (or PDF) as a schedule-level reference attachment.
     *
     * The user-facing filename is preserved for display, but the actual
     * file is written under a UUID-based name so re-uploads of the same
     * filename don't clobber each other. Stored under the `public` disk
     * at `schedule-attachments/{scheduleId}/{uuid}.{ext}` so it's reachable
     * via Storage::url() / asset('storage/...').
     */
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'file'        => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
            'description' => 'nullable|string|max:20000',
        ], [
            'file.required' => 'Pick an image (or PDF) to upload.',
            'file.max'      => 'File is too large — max 10 MB.',
            'file.mimes'    => 'Allowed types: JPG, PNG, GIF, WebP, PDF.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        // Extension derived from content, never the client filename (RCE/XSS guard).
        $ext          = \App\Support\UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);
        $stem         = Str::uuid()->toString();
        $relativeDir  = 'schedule-attachments/' . $schedule->id;
        $relativePath = $relativeDir . '/' . $stem . '.' . $ext;

        try {
            // putFileAs takes (dir, file, filename) — Laravel writes to
            // storage/app/public/<dir>/<filename> on the `public` disk.
            Storage::disk('public')->putFileAs($relativeDir, $file, $stem . '.' . $ext);
        } catch (\Throwable $e) {
            return $this->jsonFail('File upload failed: ' . $e->getMessage(), 500);
        }

        $maxOrder = (int) AsScheduleAttachment::active()
            ->where('croppingScheduleId', $schedule->id)
            ->max('sortOrder');

        $row = AsScheduleAttachment::create([
            'croppingScheduleId' => $schedule->id,
            'filename'           => $originalName,
            'storagePath'        => $relativePath,
            'mimeType'           => $file->getMimeType(),
            'fileSize'           => $file->getSize(),
            'description'        => $this->cleanDescription($request->input('description')),
            'sortOrder'          => $maxOrder + 1,
            'deleteStatus'       => 1,
        ]);

        return $this->jsonOk('Attachment uploaded.', [
            'data' => $this->serialize($row),
        ]);
    }

    /**
     * Update only the description (the file itself is immutable — to
     * change the image, delete this row and upload a new one).
     */
    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:20000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $row = AsScheduleAttachment::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$row) return $this->jsonFail('Attachment not found.', 404);

        $row->update(['description' => $this->cleanDescription($request->input('description'))]);

        return $this->jsonOk('Description updated.', ['data' => $this->serialize($row->fresh())]);
    }

    /**
     * Soft-delete the row. The file on disk is intentionally left in
     * place so an undo / restore is possible — a future cleanup job can
     * purge files for rows soft-deleted for more than N days.
     */
    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $row = AsScheduleAttachment::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$row) return $this->jsonFail('Attachment not found.', 404);

        $row->update(['deleteStatus' => 0]);
        return $this->jsonOk('Attachment removed.');
    }

    /**
     * Serialize one row for JSON responses — adds the public URL and
     * an isImage flag so the front-end can render a thumbnail vs a
     * file-icon without re-deriving from mimeType.
     */
    private function serialize(AsScheduleAttachment $row): array
    {
        return [
            'id'          => $row->id,
            'filename'    => $row->filename,
            'mimeType'    => $row->mimeType,
            'fileSize'    => (int) $row->fileSize,
            'description' => $row->description,
            'sortOrder'   => (int) $row->sortOrder,
            'url'         => $row->getPublicUrl(),
            'isImage'     => $row->isImage(),
        ];
    }

    /**
     * Descriptions are rich text from the WYSIWYG editor; run them through
     * the same allow-list the activity notes use, and null out markup that
     * carries no visible text.
     */
    private function cleanDescription(?string $html): ?string
    {
        $clean = HtmlSanitizer::rich($html);

        return filled(trim(strip_tags($clean))) ? $clean : null;
    }
}
