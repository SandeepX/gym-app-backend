<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateWorkoutPlanRequest;
use App\Http\Requests\WorkoutPlanRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\WorkoutPlan;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class WorkoutPlanController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $workoutPlans = WorkoutPlan::with(['createdBy:id,name', 'exercises'])
            ->filter($request->only(['difficulty', 'search']))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(WorkoutResource::collection($workoutPlans), 'Workout plans retrieved successfully.');
    }

    public function store(WorkoutPlanRequest $request): JsonResponse
    {
        $request->validated();

        $workoutPlan = WorkoutPlan::create([
            ...$request->only(['name', 'description', 'difficulty', 'duration_weeks', 'days_per_week', 'goals']),
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        if ($request->exercises) {
            foreach ($request->exercises as $exercise) {
                $workoutPlan->exercises()->create($exercise);
            }
        }

        $workoutPlan->load(['exercises']);

        return $this->success(new WorkoutResource($workoutPlan),
            'Workout plan created successfully.',
            Response::HTTP_CREATED
        );
    }

    public function show($workoutPlanId): JsonResponse
    {
        $workoutPlan = WorkoutPlan::with([
            'createdBy:id,name',
            'exercises',
            'members.user:id,name',
        ])->find($workoutPlanId);

        if (! $workoutPlan) {
            $this->error('Workout plan not found', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new WorkoutResource($workoutPlan),
            'Workout plan retrieved successfully.'
        );
    }

    public function update(UpdateWorkoutPlanRequest $request, $workoutPlanId): JsonResponse
    {
        try {
            $request->validated();

            $workoutPlan = WorkoutPlan::with('exercises')->find($workoutPlanId);

            if (! $workoutPlan) {
                throw new RuntimeException('Workout plan not found', ResponseAlias::HTTP_NOT_FOUND);
            }

            $workoutPlan->update($request->validated());

            return $this->success(
                new WorkoutResource($workoutPlan),
                'Workout plan updated successfully.'
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

    }

    public function delete($workoutPlanId): JsonResponse
    {
        $workoutPlan = WorkoutPlan::find($workoutPlanId);

        if (! $workoutPlan) {
            throw new RuntimeException('Workout plan not found', ResponseAlias::HTTP_NOT_FOUND);
        }

        $workoutPlan->delete();

        return $this->success([], message: 'Workout plan deleted successfully.');
    }
}
