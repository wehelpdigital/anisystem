<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleDocEntry;
use App\Models\AsScheduleDocTag;
use App\Support\HtmlSanitizer;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Unified documentation entries — introduction, critical-rule, or a custom
 * tagged reference document. Each carries rich text plus any number of files.
 * Requests are multipart so new files can ride along with the entry save.
 */
class DocEntryController extends BaseScheduleController
{
    private const TYPES = [
        AsScheduleDocEntry::TYPE_PROTOCOL,
        AsScheduleDocEntry::TYPE_INTRODUCTION,
        AsScheduleDocEntry::TYPE_CRITICAL_RULE,
        AsScheduleDocEntry::TYPE_MISCELLANEOUS,
        AsScheduleDocEntry::TYPE_CUSTOM,
    ];

    private const MAX_FILES = 20;

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $data = $this->validatedInput($request, $schedule);
        if (! is_array($data)) {
            return $data;
        }

        $files = $this->storeUploads($request, $schedule->id);

        $maxOrder = (int) AsScheduleDocEntry::active()
            ->where('croppingScheduleId', $schedule->id)
            ->max('sortOrder');

        $entry = AsScheduleDocEntry::create([
            'croppingScheduleId' => $schedule->id,
            'type' => $data['type'],
            'tagId' => $data['tagId'],
            'title' => $data['title'],
            'content' => $data['content'],
            'files' => $files,
            'sortOrder' => $maxOrder + 1,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Document added.', ['data' => $this->present($entry->fresh('tag'))]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $entry = $this->find($schedule->id, $this->queryId($request));
        if (! $entry) {
            return $this->jsonFail('Document not found.', 404);
        }

        $data = $this->validatedInput($request, $schedule);
        if (! is_array($data)) {
            return $data;
        }

        // Keep the files the client did not remove, append any new uploads.
        $keep = (array) $request->input('keepPaths', []);
        $existing = collect($entry->files ?? [])
            ->filter(fn ($f) => in_array($f['path'] ?? null, $keep, true))
            ->values()->all();
        $files = array_merge($existing, $this->storeUploads($request, $schedule->id));

        $entry->update([
            'type' => $data['type'],
            'tagId' => $data['tagId'],
            'title' => $data['title'],
            'content' => $data['content'],
            'files' => $files ?: null,
        ]);

        return $this->jsonOk('Document updated.', ['data' => $this->present($entry->fresh('tag'))]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $entry = $this->find($schedule->id, $this->queryId($request));
        if (! $entry) {
            return $this->jsonFail('Document not found.', 404);
        }

        $entry->update(['deleteStatus' => 0]);

        return $this->jsonOk('Document removed.');
    }

    /** Create a reusable custom tag and return the refreshed tag list. */
    public function storeTag(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ], [
            'name.required' => 'Enter a tag name.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $name = trim($request->input('name'));

        // Fold into an existing tag (case-insensitive) instead of duplicating.
        $tag = AsScheduleDocTag::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        if (! $tag) {
            $maxOrder = (int) AsScheduleDocTag::active()
                ->where('croppingScheduleId', $schedule->id)
                ->max('sortOrder');
            $tag = AsScheduleDocTag::create([
                'croppingScheduleId' => $schedule->id,
                'name' => $name,
                'sortOrder' => $maxOrder + 1,
                'deleteStatus' => 1,
            ]);
        }

        return $this->jsonOk('Tag added.', [
            'data' => ['id' => $tag->id, 'name' => $tag->name],
        ]);
    }

    // ------------------------------------------------------------------

    private function find(int $scheduleId, int $id): ?AsScheduleDocEntry
    {
        return AsScheduleDocEntry::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>|\Illuminate\Http\JsonResponse
     */
    private function validatedInput(Request $request, $schedule)
    {
        $tagIds = $schedule->docTags->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(self::TYPES)],
            'tagId' => ['nullable', Rule::in($tagIds)],
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:50000',
            'files' => 'nullable|array|max:' . self::MAX_FILES,
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt,xls,xlsx',
            'keepPaths' => 'nullable|array',
        ], [
            'tagId.in' => 'That tag does not belong to this schedule.',
            'files.*.mimes' => 'Allowed files: images, PDF, Word, Excel or TXT.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $type = $request->input('type');
        $tagId = $type === AsScheduleDocEntry::TYPE_CUSTOM ? ($request->input('tagId') ?: null) : null;

        if ($type === AsScheduleDocEntry::TYPE_CUSTOM && ! $tagId) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => ['tagId' => ['Pick a tag for this document.']]]);
        }

        $content = HtmlSanitizer::rich($request->input('content'));
        $content = filled(trim(strip_tags($content))) ? $content : null;
        $title = trim((string) $request->input('title')) ?: null;

        // An entry has to carry something — text, a title, or a file.
        $hasNewFiles = $request->hasFile('files');
        $keepsFiles = filled($request->input('keepPaths'));
        if (! $content && ! $title && ! $hasNewFiles && ! $keepsFiles) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => ['content' => ['Add some text or attach a file.']]]);
        }

        return [
            'type' => $type,
            'tagId' => $tagId,
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * Move any uploaded files onto the public disk and return their metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    private function storeUploads(Request $request, int $scheduleId): array
    {
        if (! $request->hasFile('files')) {
            return [];
        }

        $dir = 'schedule-doc-entries/' . $scheduleId;
        $out = [];

        foreach ($request->file('files') as $file) {
            $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx']);
            $stem = Str::uuid()->toString();
            try {
                $stored = \App\Support\MediaStore::putFile($file, 'schedule-doc-entries', $scheduleId);
                if ($stored === null) {
                    continue;
                }
            } catch (\Throwable $e) {
                continue;
            }
            $out[] = [
                'path' => $stored,
                'name' => $file->getClientOriginalName(),
                'size' => (int) $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $out;
    }

    /** Shape an entry for the JS renderer — resolves the label and file URLs. */
    private function present(AsScheduleDocEntry $entry): array
    {
        $files = array_values(array_map(fn ($f) => [
            'path' => $f['path'] ?? null,
            'name' => $f['name'] ?? 'file',
            'size' => (int) ($f['size'] ?? 0),
            'mime' => $f['mime'] ?? null,
            'url' => isset($f['path']) ? \App\Support\MediaStore::url($f['path']) : null,
            'isImage' => isset($f['mime']) && str_starts_with((string) $f['mime'], 'image/'),
        ], $entry->files ?? []));

        return [
            'id' => $entry->id,
            'type' => $entry->type,
            'tagId' => $entry->tagId,
            'typeLabel' => $entry->type_label,
            'title' => $entry->title,
            'content' => $entry->content,
            'files' => $files,
        ];
    }
}
