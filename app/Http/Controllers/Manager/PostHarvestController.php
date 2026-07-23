<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsSchedulePostHarvest;
use App\Support\HtmlSanitizer;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    public function uploadImage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

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

        $file = $request->file('image');
        // Extension derived from content, never the client filename (RCE/XSS guard).
        $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $stem = Str::uuid()->toString();
        $relativeDir = 'schedule-post-harvest/' . $schedule->id;

        try {
            Storage::disk('public')->putFileAs($relativeDir, $file, $stem . '.' . $ext);
        } catch (\Throwable $e) {
            return $this->jsonFail('Photo upload failed: ' . $e->getMessage(), 500);
        }

        $path = $relativeDir . '/' . $stem . '.' . $ext;

        return $this->jsonOk('Photo uploaded.', [
            'data' => ['path' => $path, 'url' => Storage::disk('public')->url($path)],
        ]);
    }

    // ------------------------------------------------------------------

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
            'notes' => 'nullable|string|max:20000',
            'imagePath' => 'nullable|string|max:500',
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

        return $data;
    }

    /** Shape a row for the JS renderer (lot name resolved, value precomputed). */
    private function present(AsSchedulePostHarvest $o): array
    {
        return array_merge($o->toArray(), [
            'lotName' => $o->lotId ? optional($o->lot)->lotName : null,
            'grossValue' => $o->gross_value,
            'categoryLabel' => AsSchedulePostHarvest::CATEGORIES[$o->category] ?? $o->category,
            'imageUrl' => $o->imagePath ? Storage::disk('public')->url($o->imagePath) : null,
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
