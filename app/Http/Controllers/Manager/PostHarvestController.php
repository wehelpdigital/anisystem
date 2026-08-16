<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsSchedulePostHarvest;
use App\Support\HtmlSanitizer;
use App\Support\MediaStore;
use App\Support\VideoOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Post-harvest observations for a cropping schedule — what the season actually
 * produced, and what is worth carrying into the next one.
 */
class PostHarvestController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load('lots');

        $observations = AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('observationDate')
            ->orderByDesc('id')
            ->get();

        return view('sm.post-harvest', [
            'schedule' => $schedule,
            'observations' => $observations,
            'categories' => AsSchedulePostHarvest::CATEGORIES,
            'summary' => $this->summarise($observations),
        ]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $data = $this->validated($request, $schedule);
        if (! is_array($data)) {
            return $data;
        }

        $observation = AsSchedulePostHarvest::create($data + [
            'croppingScheduleId' => $schedule->id,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Observation added.', ['data' => $this->present($observation)]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $observation = $this->find($schedule->id, $this->queryId($request));
        if (! $observation) {
            return $this->jsonFail('Observation not found.', 404);
        }

        $data = $this->validated($request, $schedule);
        if (! is_array($data)) {
            return $data;
        }

        $observation->update($data);

        return $this->jsonOk('Observation updated.', ['data' => $this->present($observation->fresh())]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $observation = $this->find($schedule->id, $this->queryId($request));
        if (! $observation) {
            return $this->jsonFail('Observation not found.', 404);
        }

        $observation->update(['deleteStatus' => 0]);

        return $this->jsonOk('Observation deleted.');
    }

    /** Undo support — the row is only ever soft-deleted. */
    public function restore(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $observation = AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('id', $this->queryId($request))
            ->first();

        if (! $observation) {
            return $this->jsonFail('Observation not found.', 404);
        }

        $observation->update(['deleteStatus' => 1]);

        return $this->jsonOk('Observation restored.', ['data' => $this->present($observation->fresh())]);
    }

    /**
     * Somewhere to put whatever the attach bar produced.
     *
     * The route in front of this is still called image-upload, and
     * routes/web.php is not ours to rename, so the method asks what actually
     * arrived rather than trusting what it was called. A photo and a clip take
     * different roads: a photo is handed straight to the store, a clip has to
     * be squeezed to 720p first or a minute of phone video costs the field
     * connection its afternoon.
     */
    public function uploadImage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        return $request->hasFile('video')
            ? $this->storeVideo($request, $schedule)
            : $this->storePhoto($request, $schedule);
    }

    // ------------------------------------------------------------------

    private function storePhoto(Request $request, $schedule)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ], [
            'image.required' => 'Pick a photo to upload.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Allowed types: JPG, PNG, WebP, GIF.',
            'image.max' => 'Photo is too large — max 8 MB.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        try {
            // The path the store gives back is the only one that means
            // anything. This used to answer with a path assembled here out of
            // a fresh uuid and a folder the store does not use, so every
            // observation photo since was filed correctly and then pointed at
            // from an address that has never held a file.
            $path = MediaStore::putFile($request->file('image'), 'schedule-post-harvest', $schedule->id);
        } catch (\Throwable $e) {
            return $this->jsonFail('Photo upload failed: ' . $e->getMessage(), 500);
        }

        if ($path === null) {
            return $this->jsonFail('Photo upload failed.', 500);
        }

        return $this->jsonOk('Photo uploaded.', [
            'data' => ['type' => 'image', 'path' => $path, 'url' => MediaStore::url($path)],
        ]);
    }

    private function storeVideo(Request $request, $schedule)
    {
        $validator = Validator::make($request->all(), [
            // One entry per extension kindOf() calls a video, so nothing gets
            // through here that the card will then try to show as a photo —
            // and nothing the attach bar routes to this endpoint is turned
            // away as "not a supported video" for want of a mimetype. x-m4v
            // was the other half of that gap.
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v,video/x-msvideo|max:307200',
        ], [
            'video.required' => 'Pick a video first.',
            'video.max' => 'Video is too large — max 300 MB.',
            'video.mimetypes' => 'That file is not a supported video.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        try {
            $out = VideoOptimizer::storeCompressed($request->file('video'), 'schedule-post-harvest/' . $schedule->id . '/videos');
        } catch (\Throwable $e) {
            return $this->jsonFail('Video processing failed: ' . $e->getMessage(), 500);
        }

        // Compress here, keep there — same trade the notes make: the clip
        // crosses to the mother app once it is small, and survives a deploy.
        foreach (['video', 'poster'] as $part) {
            $local = $out[$part] ?? null;
            if (! $local || ! MediaStore::enabled()) {
                continue;
            }
            $kept = MediaStore::putBinary(
                Storage::disk('public')->get($local),
                'schedule-post-harvest',
                pathinfo($local, PATHINFO_EXTENSION) ?: ($part === 'poster' ? 'jpg' : 'mp4'),
                $schedule->id
            );
            if ($kept && $kept !== $local) {
                Storage::disk('public')->delete($local);
                $out[$part] = $kept;
            }
        }

        // The poster travels back so the sheet can show a frame instead of a
        // black box, but it is not stored with the observation: there is one
        // column of paths and it holds one path per attachment. What the card
        // shows later is the clip itself, told apart by its name.
        return $this->jsonOk('Video attached.', [
            'data' => [
                'type' => 'video',
                'path' => $out['video'],
                'url' => MediaStore::url($out['video']),
                'posterUrl' => ! empty($out['poster']) ? MediaStore::url($out['poster']) : null,
            ],
        ]);
    }

    /**
     * Photo or clip? The name is all there is to go on — see validated().
     *
     * The list of extensions lives in SeasonMedia because three copies had
     * drifted: this one said an AVI was a photo while the validator below and
     * the attach bar's VID_RE both called it a video, so an AVI was accepted,
     * filed as a picture and rendered in an <img>. One list, three readers.
     * Kept as a method here because the Blade card calls it by this name.
     */
    public static function kindOf(?string $path): string
    {
        return \App\Support\SeasonMedia::kindOf($path);
    }

    private function find(int $scheduleId, int $id): ?AsSchedulePostHarvest
    {
        return AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>|\Illuminate\Http\JsonResponse
     */
    private function validated(Request $request, $schedule)
    {
        $lotIds = $schedule->lots->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'category' => ['required', Rule::in(array_keys(AsSchedulePostHarvest::CATEGORIES))],
            'observationDate' => 'nullable|date',
            'lotId' => ['nullable', Rule::in($lotIds)],
            'yieldAmount' => 'nullable|numeric|min:0|max:99999999',
            'yieldUnit' => 'nullable|string|max:24',
            'moisturePercent' => 'nullable|numeric|min:0|max:100',
            'pricePerUnit' => 'nullable|numeric|min:0|max:99999999',
            'buyer' => 'nullable|string|max:191',
            // Whatever this kind of observation asked for beyond the columns:
            // a pest's severity, a typhoon's length, what to change next year.
            'details' => 'nullable|array|max:20',
            'details.*' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:20000',
            'imagePath' => 'nullable|string|max:500',
            'imagePaths' => 'nullable|array|max:20',
            'imagePaths.*' => 'string|max:500',
        ], [
            'lotId.in' => 'That lot does not belong to this schedule.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $data = $validator->validated();
        // Notes are rich text from the client, so they go through the same
        // allow-list the activity descriptions use.
        $data['notes'] = filled($data['notes'] ?? null) ? HtmlSanitizer::rich($data['notes']) : null;
        $data['lotId'] = $data['lotId'] ?? null;
        // Only the answers this category actually asks for are kept, so a
        // category switched mid-form cannot leave the previous one's answers
        // behind where nothing will ever show them again.
        $asked = collect(\App\Support\PostHarvestFields::for($data['category']))->pluck('key')->all();
        $details = collect($data['details'] ?? [])
            ->filter(fn ($v, $k) => in_array($k, $asked, true) && filled($v))
            ->all();
        $data['details'] = $details ?: null;

        // Normalise the attachment list. It holds clips as well as photos now,
        // and it holds them as a flat list of paths, because that is what
        // everything else reading this column expects — App\Support\SeasonMedia
        // merges it straight into the season's media. So which is which is read
        // off the file name, the same way the Gallery tells an album's clips
        // from its pictures.
        $paths = collect($data['imagePaths'] ?? [])
            ->filter(fn ($p) => filled($p))
            ->values()->all();
        $data['imagePaths'] = $paths ?: null;
        // The legacy single field stays a photo: it is what the old card shows
        // as an <img>, and an mp4 in there renders as a broken picture.
        $firstPhoto = collect($paths)->first(fn ($p) => self::kindOf($p) === 'image');
        $data['imagePath'] = $firstPhoto ?? ($data['imagePath'] ?? null);

        return $data;
    }

    /** Shape a row for the JS renderer (lot name resolved, value precomputed). */
    private function present(AsSchedulePostHarvest $o): array
    {
        // Details as stored (for the form) and as sentences (for the card):
        // "severe" is what we keep, "Severe — serious loss" is what a person
        // reads.
        $detailRows = collect($o->details ?? [])
            ->map(fn ($v, $k) => [
                'key' => $k,
                'label' => \App\Support\PostHarvestFields::questionFor((string) $o->category, $k) ?: $k,
                'value' => \App\Support\PostHarvestFields::labelFor((string) $o->category, $k, $v),
            ])
            ->values()->all();
        // Prefer the multi-attachment list; fall back to the legacy single path.
        $paths = ! empty($o->imagePaths) ? $o->imagePaths : array_filter([$o->imagePath]);
        $images = array_values(array_map(fn ($p) => [
            'type' => self::kindOf($p),
            'path' => $p,
            'url' => \App\Support\MediaStore::url($p),
        ], $paths));

        return array_merge($o->toArray(), [
            'lotName' => $o->lotId ? optional($o->lot)->lotName : null,
            'grossValue' => $o->gross_value,
            'categoryLabel' => AsSchedulePostHarvest::CATEGORIES[$o->category] ?? $o->category,
            'detailRows' => $detailRows,
            'images' => $images,
            'imageUrl' => $images[0]['url'] ?? null,
        ]);
    }

    /**
     * Season totals across every observation that carries figures.
     *
     * @param  \Illuminate\Support\Collection<int, AsSchedulePostHarvest>  $observations
     */
    private function summarise($observations): array
    {
        // Yields only add up within a unit, so they are grouped by it.
        $byUnit = [];
        $revenue = 0.0;
        $moistures = [];

        foreach ($observations as $o) {
            if ($o->yieldAmount !== null) {
                $unit = trim((string) $o->yieldUnit) ?: 'unit';
                $byUnit[$unit] = ($byUnit[$unit] ?? 0) + (float) $o->yieldAmount;
            }
            if ($o->gross_value !== null) {
                $revenue += $o->gross_value;
            }
            if ($o->moisturePercent !== null) {
                $moistures[] = (float) $o->moisturePercent;
            }
        }

        return [
            'count' => $observations->count(),
            'yields' => $byUnit,
            'revenue' => $revenue > 0 ? round($revenue, 2) : null,
            'avgMoisture' => $moistures ? round(array_sum($moistures) / count($moistures), 1) : null,
        ];
    }
}
