<?php

namespace App\Http\Controllers\Api;

use App\Enums\EquipmentStatusEnum;
use App\Http\Requests\EquipmentMaintenanceLogRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\EquipmentMaintenanceLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $equipment = Equipment::with('maintenanceLogs')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($equipment, 'Equipment retrieved successfully.');
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $validatedData['serial_number'] = Equipment::generateSequenceNumber('EQ', 'serial_number');

        $equipment = Equipment::create($validatedData);

        return $this->success($equipment, 'Equipment added successfully.', 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return $this->success(
            $equipment->load(['maintenanceLogs.performedBy:id,name']),
            'Equipment retrieved successfully.'
        );
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        $request->validated();

        $equipment->update($request->validated());

        return $this->success($equipment->fresh(), 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return $this->success([], message: 'Equipment deleted successfully.');
    }

    /**
     * Log maintenance.
     */
    public function logMaintenance(EquipmentMaintenanceLogRequest $request, Equipment $equipment): JsonResponse
    {
        $request->validated();

        $log = EquipmentMaintenanceLog::create([
            ...$request->validated(),
            'equipment_id' => $equipment->id,
            'performed_by' => $request->user()->id,
        ]);

        $equipment->update([
            'last_maintenance_date' => $request->performed_at,
            'next_maintenance_date' => $request->next_maintenance_date,
            'status' => 'active',
        ]);

        return $this->success(
            $log->load('performedBy:id,name'),
            'Maintenance logged successfully.',
            201
        );
    }

    /**
     * Equipment due for maintenance.
     */
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

    /**
     * Equipment stats.
     */
    public function stats(): JsonResponse
    {
        $stats = Equipment::query()
            ->selectRaw('
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE status = ?) AS active,
            COUNT(*) FILTER (WHERE status = ?) AS under_maintenance,
            COUNT(*) FILTER (WHERE status = ?) AS retired,
            COUNT(*) FILTER (WHERE next_maintenance_date <= NOW()) AS due_for_maintenance
        ', [
                EquipmentStatusEnum::Active->value,
                EquipmentStatusEnum::Maintenance->value,
                EquipmentStatusEnum::Retired->value,
            ])
            ->first();

        return $this->success((array) $stats, 'Equipment stats retrieved.');
    }
}
