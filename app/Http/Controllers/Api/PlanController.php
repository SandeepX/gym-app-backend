<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatusEnum;
use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlanController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $plans = Plan::withCount('subscriptions')
            ->filter($request->only(['type', 'is_active', 'search']))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(PlanResource::collection($plans), 'Plans retrieved successfully.');
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return $this->success(new PlanResource($plan), 'Plan created successfully.', Response::HTTP_CREATED
        );
    }

    public function show($planId): JsonResponse
    {
        $plan = Plan::withCount('subscriptions')->find($planId);

        if (! $plan) {
            return $this->error('Plan not found', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new PlanResource($plan), 'Plan retrieved successfully.');
    }

    public function update(PlanRequest $request, $planId): JsonResponse
    {
        $validatedData = $request->validated();

        $plan = Plan::find($planId);

        if (! $plan) {
            return $this->error('Plan not found', Response::HTTP_NOT_FOUND);
        }

        $plan->update($validatedData);

        return $this->success(new PlanResource($plan->fresh()), 'Plan updated successfully.');
    }

    public function delete($planId): JsonResponse
    {
        $plan = Plan::with('subscriptions')->find($planId);

        if (! $plan) {
            return $this->error('Plan not found', Response::HTTP_NOT_FOUND);
        }

        if ($plan->subscriptions()->where('status', SubscriptionStatusEnum::Active)->exists()) {
            return $this->error('Cannot delete plan with active subscriptions.',
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $plan->delete();

        return $this->success([], message: 'Plan deleted successfully.');
    }
}
