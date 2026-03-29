<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BodyMeasurementRequest;
use App\Http\Resources\BodyMeasurementResource;
use App\Models\BodyMeasurement;
use App\Services\MemberService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BodyMeasurementController
{
    use ApiResponseTrait;

    /**
     * Record new measurement.
     */
    public function __construct(public MemberService $memberService) {}

    public function store(BodyMeasurementRequest $request, $memberId): JsonResponse
    {
        try {
            $data = $request->validated();

            $member = $this->memberService->getMemberDetailById($memberId);

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

            return $this->success(new BodyMeasurementResource($measurement),
                'Body measurement recorded successfully.',
                Response::HTTP_CREATED);

        } catch (Exception $th) {
            return $this->error($th->getMessage(), $th->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update measurement.
     */
    public function update(BodyMeasurementRequest $request, $measurementId): JsonResponse
    {
        $data = $request->validated();

        $measurement = BodyMeasurement::find($measurementId);

        if (! $measurement) {
            return $this->error('Member Body Measurement Detail Not Found', Response::HTTP_NOT_FOUND);
        }

        if (! empty($data['weight']) && ! empty($data['height'])) {
            $data['bmi'] = BodyMeasurement::calculateBmi($data['weight'], $data['height']);
        }

        $measurement->update($data);

        $measurement->fresh()->load('recordedBy:id,name');

        return $this->success(new BodyMeasurementResource($measurement), 'Body measurement updated successfully.');
    }

    public function progress($memberId): JsonResponse
    {
        try {
            $member = $this->memberService->getMemberDetailById($memberId);

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

        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }
}
