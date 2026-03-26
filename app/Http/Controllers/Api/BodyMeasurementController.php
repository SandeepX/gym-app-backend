<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BodyMeasurementRequest;
use App\Models\BodyMeasurement;
use App\Models\Member;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BodyMeasurementController
{
    use ApiResponseTrait;

    /**
     * Get all measurements for a member.
     */
    public function index(Request $request, Member $member): JsonResponse
    {
        $measurements = BodyMeasurement::where('member_id', $member->id)
            ->with('recordedBy:id,name')
            ->when($request->from_date, fn ($q) => $q->whereDate('measured_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('measured_at', '<=', $request->to_date))
            ->latest('measured_at')
            ->get();

        return $this->success([
            'member' => $member->user->name,
            'total' => $measurements->count(),
            'latest' => $measurements->first(),
            'measurements' => $measurements,
        ], 'Body measurements retrieved successfully.');
    }

    /**
     * Record new measurement.
     */
    public function store(BodyMeasurementRequest $request, Member $member): JsonResponse
    {
        $data = $request->validated();

        // Auto calculate BMI
        if (! empty($data['weight']) && ! empty($data['height'])) {
            $data['bmi'] = BodyMeasurement::calculateBmi(
                $data['weight'],
                $data['height']
            );
        }

        $data['member_id'] = $member->id;
        $data['recorded_by'] = $request->user()->id;
        $data['measured_at'] = $data['measured_at'] ?? now()->toDateString();

        $measurement = BodyMeasurement::create($data);

        return $this->success(
            $measurement->load('recordedBy:id,name'),
            'Body measurement recorded successfully.',
            201
        );
    }

    /**
     * Show single measurement.
     */
    public function show(Member $member, BodyMeasurement $measurement): JsonResponse
    {
        return $this->success([
            'measurement' => $measurement->load('recordedBy:id,name'),
            'bmi_category' => $measurement->bmiCategory(),
        ], 'Body measurement retrieved successfully.');
    }

    /**
     * Update measurement.
     */
    public function update(BodyMeasurementRequest $request, Member $member, BodyMeasurement $measurement): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['weight']) && ! empty($data['height'])) {
            $data['bmi'] = BodyMeasurement::calculateBmi($data['weight'], $data['height']);
        }

        $measurement->update($data);

        return $this->success(
            $measurement->fresh()->load('recordedBy:id,name'),
            'Body measurement updated successfully.'
        );
    }

    /**
     * Delete measurement.
     */
    public function destroy(Member $member, BodyMeasurement $measurement): JsonResponse
    {
        $measurement->delete();

        return $this->success([], message: 'Body measurement deleted successfully.');
    }

    /**
     * Progress chart data.
     */
    public function progress(Member $member): JsonResponse
    {
        $measurements = BodyMeasurement::where('member_id', $member->id)
            ->latest('measured_at')
            ->get(['measured_at', 'weight', 'bmi', 'body_fat_percentage', 'muscle_mass', 'waist']);

        return $this->success([
            'member' => $member->user->name,
            'progress' => $measurements,
            'summary' => [
                'first_recorded' => $measurements->last()?->measured_at?->format('Y-m-d'),
                'last_recorded' => $measurements->first()?->measured_at?->format('Y-m-d'),
                'total_records' => $measurements->count(),
                'weight_change' => $measurements->count() > 1
                    ? round($measurements->first()->weight - $measurements->last()->weight, 2)
                    : null,
            ],
        ], 'Progress data retrieved successfully.');
    }
}
