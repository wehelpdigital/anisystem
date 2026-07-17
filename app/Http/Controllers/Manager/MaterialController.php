<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialController extends BaseScheduleController
{
    private array $allowedTypes = ['granular', 'foliar', 'pesticide', 'herbicide', 'molluscicide', 'fungicide', 'fertilizer', 'seed', 'other'];
    private array $allowedUnits = ['kg', 'g', 'ml', 'l', 'bottle', 'sachet', 'piece', 'pack'];

    /**
     * Materials module page (?id={scheduleId}).
     */
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load('materials');

        return view('sm.materials', [
            'schedule' => $schedule,
            'allowedTypes' => $this->allowedTypes,
            'allowedUnits' => $this->allowedUnits,
        ]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'materialName'  => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'materialType'  => 'required|in:' . implode(',', $this->allowedTypes),
            'unitOfMeasure' => 'required|in:' . implode(',', $this->allowedUnits),
            'priceAmount'   => 'required|numeric|min:0',
            'priceQuantity' => 'required|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $material = AsScheduleMaterial::create([
            'croppingScheduleId' => $schedule->id,
            'materialName'  => $request->materialName,
            'description'   => $request->description,
            'materialType'  => $request->materialType,
            'unitOfMeasure' => $request->unitOfMeasure,
            'priceAmount'   => $request->priceAmount,
            'priceQuantity' => $request->priceQuantity,
            'deleteStatus'  => 1,
        ]);

        return $this->jsonOk('Material added.', ['data' => $material]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $material = AsScheduleMaterial::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$material) return $this->jsonFail('Material not found.', 404);

        $validator = Validator::make($request->all(), [
            'materialName'  => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'materialType'  => 'required|in:' . implode(',', $this->allowedTypes),
            'unitOfMeasure' => 'required|in:' . implode(',', $this->allowedUnits),
            'priceAmount'   => 'required|numeric|min:0',
            'priceQuantity' => 'required|numeric|min:0.0001',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $material->update($request->only([
            'materialName', 'description', 'materialType', 'unitOfMeasure', 'priceAmount', 'priceQuantity',
        ]));

        return $this->jsonOk('Material updated.', ['data' => $material]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $material = AsScheduleMaterial::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$material) return $this->jsonFail('Material not found.', 404);

        $material->update(['deleteStatus' => 0]);
        return $this->jsonOk('Material deleted.');
    }
}
