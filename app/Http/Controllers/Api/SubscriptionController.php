<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatusEnum;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Requests\SubscriptionUpdateRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SubscriptionController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with(['member.user', 'plan'])
            ->applyFilters($request)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(SubscriptionResource::collection($subscriptions),
            'Subscriptions retrieved successfully.'
        );
    }

    public function store(SubscriptionRequest $request): JsonResponse
    {
        $request->validated();

        $plan = Plan::findOrFail($request->plan_id);
        $endDate = Carbon::parse($request->start_date)->addDays($plan->duration_days);

        $subscription = Subscription::create([
            'subscription_number' => Subscription::generateSequenceNumber('SUB', 'subscription_number'),
            'member_id' => $request->member_id,
            'plan_id' => $request->plan_id,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'status' => SubscriptionStatusEnum::Active,
            'auto_renew' => $request->boolean('auto_renew'),
            'notes' => $request->notes,
        ]);

        $subscription->load(['member.user', 'plan']);

        return $this->success(new SubscriptionResource($subscription),
            'Subscription created successfully.',
            Response::HTTP_CREATED
        );
    }

    public function show($subscriptionId): JsonResponse
    {
        $subscription = Subscription::with(['member.user', 'plan', 'payments'])->find($subscriptionId);

        if (! $subscription) {
            return $this->error('Subscription not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new SubscriptionResource($subscription),
            'Subscription retrieved successfully.'
        );
    }

    public function update(SubscriptionUpdateRequest $request, $subscriptionId): JsonResponse
    {
        try {
            $request->validated();

            $subscription = Subscription::with(['member.user', 'plan', 'payments'])->find($subscriptionId);

            if (! $subscription) {
                return $this->error('Subscription not found.', ResponseAlias::HTTP_NOT_FOUND);
            }

            $subscription->update($request->only(['auto_renew', 'notes', 'status']));

            return $this->success(new SubscriptionResource($subscription),
                'Subscription updated successfully.');

        } catch (\Exception $exception) {
            return $this->error($exception->getMessage(), ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($subscriptionId): JsonResponse
    {
        $subscription = Subscription::with(['member.user', 'plan', 'payments'])->find($subscriptionId);

        if (! $subscription) {
            return $this->error('Subscription not found.', Response::HTTP_NOT_FOUND);
        }

        $subscription->update([
            'status' => SubscriptionStatusEnum::Cancelled,
        ]);

        $subscription->delete();

        return $this->success([], message: 'Subscription cancelled successfully.');
    }

    public function freeze(Request $request, Subscription $subscription): JsonResponse
    {
        $request->validate([
            'freeze_start' => ['required', 'date', 'after_or_equal:today'],
            'freeze_end' => ['required', 'date', 'after:freeze_start'],
        ]);

        if ($subscription->status !== SubscriptionStatusEnum::Active) {
            return $this->error('Only active subscriptions can be frozen.', 422);
        }

        $freezeDays = Carbon::parse($request->freeze_start)
            ->diffInDays(Carbon::parse($request->freeze_end));

        $totalFreezeDays = $subscription->freeze_days_used + $freezeDays;

        if ($totalFreezeDays > $subscription->plan->max_freeze_days) {
            return $this->error(
                "Exceeds max freeze days allowed ({$subscription->plan->max_freeze_days} days).",
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $subscription->update([
            'status' => SubscriptionStatusEnum::Frozen,
            'freeze_start' => $request->freeze_start,
            'freeze_end' => $request->freeze_end,
            'freeze_days_used' => $totalFreezeDays,
            'end_date' => Carbon::parse($subscription->end_date)->addDays($freezeDays),
        ]);

        return $this->success(
            SubscriptionResource::make($subscription->fresh()->load(['member.user', 'plan'])),
            'Subscription frozen successfully.'
        );
    }

    public function unfreeze(Subscription $subscription): JsonResponse
    {
        if ($subscription->status !== SubscriptionStatusEnum::Frozen) {
            return $this->error('Subscription is not frozen.', 422);
        }

        $subscription->update([
            'status' => SubscriptionStatusEnum::Active,
            'freeze_start' => null,
            'freeze_end' => null,
        ]);

        return $this->success(
            SubscriptionResource::make($subscription->fresh()->load(['member.user', 'plan'])),
            'Subscription unfrozen successfully.'
        );
    }

    public function renew(Subscription $subscription): JsonResponse
    {
        $plan = $subscription->plan;
        $newEnd = Carbon::parse($subscription->end_date)->addDays($plan->duration_days);

        $newSubscription = Subscription::create([
            'subscription_number' => Subscription::generateSequenceNumber('SUB', 'subscription_number'),
            'member_id' => $subscription->member_id,
            'plan_id' => $subscription->plan_id,
            'start_date' => $subscription->end_date,
            'end_date' => $newEnd,
            'status' => SubscriptionStatusEnum::Active,
            'auto_renew' => $subscription->auto_renew,
        ]);

        return $this->success(
            SubscriptionResource::make($newSubscription->load(['member.user', 'plan'])),
            'Subscription renewed successfully.',
            Response::HTTP_CREATED
        );
    }
}
