<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends BaseScheduleController
{
    /**
     * Services module page (?id={scheduleId}).
     */
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load('services');

        return view('sm.services', ['schedule' => $schedule]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'serviceName' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'serviceCost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $service = AsScheduleService::create([
            'croppingScheduleId' => $schedule->id,
            'serviceName' => $request->serviceName,
            'description' => $request->description,
            'serviceCost' => $request->serviceCost,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Service added.', ['data' => $service]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $service = AsScheduleService::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$service) return $this->jsonFail('Service not found.', 404);

        $validator = Validator::make($request->all(), [
            'serviceName' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'serviceCost' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $service->update($request->only(['serviceName', 'description', 'serviceCost']));
        return $this->jsonOk('Service updated.', ['data' => $service]);
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
}
