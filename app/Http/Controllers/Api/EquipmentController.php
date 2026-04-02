<?php

namespace App\Http\Controllers\Api;

use App\Enums\EquipmentStatusEnum;
use App\Http\Requests\EquipmentMaintenanceLogRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EquipmentController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $equipment = Equipment::with('maintenanceLogs')
            ->filter($request->only(['status', 'category', 'search']))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(EquipmentResource::collection($equipment),
            'Equipment retrieved successfully.');
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $validatedData['serial_number'] = Equipment::generateSequenceNumber('EQ', 'serial_number');

        $equipment = Equipment::create($validatedData);

        return $this->success(new EquipmentResource($equipment),
            'Equipment added successfully.', Response::HTTP_CREATED);
    }

    public function show($equipmentId): JsonResponse
    {
        $equipment = Equipment::with(['maintenanceLogs.performedBy:id,name'])->find($equipmentId);

        if (! $equipment) {
            return $this->error('Equipment not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new EquipmentResource($equipment), 'Equipment retrieved successfully.');
    }

    public function destroy($equipmentId): JsonResponse
    {
        $equipment = Equipment::find($equipmentId);

        if (! $equipment) {
            return $this->error('Equipment not found.', Response::HTTP_NOT_FOUND);
        }

        $equipment->delete();

        return $this->success([], message: 'Equipment deleted successfully.');
    }

    public function logMaintenance(EquipmentMaintenanceLogRequest $request, $equipmentId): JsonResponse
    {
        $equipment = Equipment::find($equipmentId);

        if (! $equipment) {
            return $this->error('Equipment not found.', Response::HTTP_NOT_FOUND);
        }

        $result = DB::transaction(function () use ($request, $equipment) {
            $log = $equipment->maintenanceLogs()->create([
                ...$request->validated(),
                'performed_by' => $request->user()->id,
            ]);

            $equipment->update([
                'last_maintenance_date' => $request->performed_at,
                'next_maintenance_date' => $request->next_maintenance_date,
                'status' => EquipmentStatusEnum::Active->value,
            ]);

            return $log;
        });

        return $this->success(
            new EquipmentResource($equipment->fresh()->load(['maintenanceLogs', 'maintenanceLogs.performedBy:id,name'])),
            'Maintenance logged successfully.',
            Response::HTTP_CREATED
        );
    }

    public function update(StoreEquipmentRequest $request, $equipmentId): JsonResponse
    {
        $request->validated();

        $equipment = Equipment::find($equipmentId);

        if (! $equipment) {
            return $this->error('Equipment not found.', Response::HTTP_NOT_FOUND);
        }

        $equipment->update($request->validated());

        return $this->success(new EquipmentResource($equipment->fresh()),
            'Equipment updated successfully.');
    }

    public function dueMaintenance(): JsonResponse
    {
        $equipment = Equipment::where('status', EquipmentStatusEnum::Active)
            ->where('next_maintenance_date', '<=', now())
            ->orWhere('next_maintenance_date', null)
            ->get();

        return $this->success([
            'total' => $equipment->count(),
            'data' => $equipment,
        ], 'Equipment due for maintenance.');
    }
}
