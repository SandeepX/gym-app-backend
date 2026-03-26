<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberWorkoutPlanEnum;
use App\Http\Requests\AssignMemberToWorkOutPlanRequest;
use App\Http\Requests\UpdateWorkoutPlanRequest;
use App\Http\Requests\WorkoutPlanRequest;
use App\Models\Member;
use App\Models\WorkoutPlan;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutPlanController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $plans = WorkoutPlan::with(['createdBy:id,name', 'exercises'])
            ->when($request->difficulty, fn ($q) => $q->where('difficulty', $request->difficulty))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->where('is_active', true)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($plans, 'Workout plans retrieved successfully.');
    }

    public function store(WorkoutPlanRequest $request): JsonResponse
    {
        $request->validated();

        $plan = WorkoutPlan::create([
            ...$request->only(['name', 'description', 'difficulty', 'duration_weeks', 'days_per_week', 'goals']),
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        if ($request->exercises) {
            foreach ($request->exercises as $exercise) {
                $plan->exercises()->create($exercise);
            }
        }

        return $this->success(
            $plan->load(['createdBy:id,name', 'exercises']),
            'Workout plan created successfully.',
            201
        );
    }

    public function show(WorkoutPlan $workoutPlan): JsonResponse
    {
        return $this->success(
            $workoutPlan->load(['createdBy:id,name', 'exercises', 'members.user:id,name']),
            'Workout plan retrieved successfully.'
        );
    }

    public function update(UpdateWorkoutPlanRequest $request, WorkoutPlan $workoutPlan): JsonResponse
    {
        $request->validated();

        $workoutPlan->update($request->validated());

        return $this->success(
            $workoutPlan->fresh()?->load(['createdBy:id,name', 'exercises']),
            'Workout plan updated successfully.'
        );
    }

    public function destroy(WorkoutPlan $workoutPlan): JsonResponse
    {
        $workoutPlan->delete();

        return $this->success([], message: 'Workout plan deleted successfully.');
    }

    /**
     * Assign workout plan to member.
     */
    public function assignToMember(AssignMemberToWorkOutPlanRequest $request, WorkoutPlan $workoutPlan): JsonResponse
    {
        $request->validated();

        $member = Member::findOrFail($request->member_id);

        $exists = $workoutPlan->members()
            ->where('member_id', $member->id)
            ->wherePivot('status', MemberWorkoutPlanEnum::ACTIVE->value)
            ->exists();

        if ($exists) {
            return $this->error('This workout plan is already assigned to the member.', 422);
        }

        $endDate = Carbon::parse($request->start_date)
            ->addWeeks($workoutPlan->duration_weeks)
            ->toDateString();

        $workoutPlan->members()->attach($member->id, [
            'assigned_by' => $request->user()->id,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'status' => MemberWorkoutPlanEnum::ACTIVE->value,
            'notes' => $request->notes,
        ]);

        return $this->success([
            'member' => $member->user->name,
            'workout_plan' => $workoutPlan->name,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
        ], 'Workout plan assigned to member successfully.');
    }

    /**
     * Get member workout plans.
     */
    public function memberPlans(Member $member): JsonResponse
    {
        $plans = $member->load(['workoutPlans.exercises'])->workoutPlans;

        return $this->success($plans, 'Member workout plans retrieved successfully.');
    }
}
