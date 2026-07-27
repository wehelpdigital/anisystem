<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends BaseScheduleController
{
    /**
     * Services module page (?id={scheduleId}).
     */
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load(['services.lot', 'lots']);

        return view('sm.services', ['schedule' => $schedule]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = $this->validator($request, $schedule);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $service = AsScheduleService::create([
            'croppingScheduleId' => $schedule->id,
            'lotId' => $request->input('lotId') ?: null,
            'serviceName' => $request->serviceName,
            'description' => $request->description,
            'serviceCost' => $request->serviceCost,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Service added.', ['data' => $this->present($service)]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $service = AsScheduleService::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$service) return $this->jsonFail('Service not found.', 404);

        $validator = $this->validator($request, $schedule);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $service->update([
            'lotId' => $request->input('lotId') ?: null,
            'serviceName' => $request->serviceName,
            'description' => $request->description,
            'serviceCost' => $request->serviceCost,
        ]);

        return $this->jsonOk('Service updated.', ['data' => $this->present($service->fresh('lot'))]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $service = AsScheduleService::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$service) return $this->jsonFail('Service not found.', 404);

        $service->update(['deleteStatus' => 0]);
        return $this->jsonOk('Service deleted.');
    }

    /** Shared validation — lotId, when given, must belong to this schedule. */
    private function validator(Request $request, $schedule): \Illuminate\Validation\Validator
    {
        $lotIds = $schedule->lots->pluck('id')->all();

        return Validator::make($request->all(), [
            'serviceName' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'serviceCost' => 'required|numeric|min:0',
            'lotId' => ['nullable', Rule::in($lotIds)],
        ], [
            'lotId.in' => 'That lot does not belong to this schedule.',
        ]);
    }

    /** Shape a row for the JS renderer, resolving the lot name. */
    private function present(AsScheduleService $service): array
    {
        return array_merge($service->toArray(), [
            'lotName' => $service->lotId ? optional($service->lot)->lotName : null,
        ]);
    }
}
