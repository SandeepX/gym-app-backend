<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $plans = Plan::withCount('subscriptions')
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->is_active, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success(
            PlanResource::collection($plans),
            'Plans retrieved successfully.'
        );
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return $this->success(
            PlanResource::make($plan),
            'Plan created successfully.',
            201
        );
    }

    public function show(Plan $plan): JsonResponse
    {
        return $this->success(
            PlanResource::make($plan->loadCount('subscriptions')),
            'Plan retrieved successfully.'
        );
    }

    public function update(PlanRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return $this->success(
            PlanResource::make($plan->fresh()),
            'Plan updated successfully.'
        );
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return $this->error(
                'Cannot delete plan with active subscriptions.',
                422
            );
        }

        $plan->delete();

        return $this->success(message: 'Plan deleted successfully.');
    }
}
