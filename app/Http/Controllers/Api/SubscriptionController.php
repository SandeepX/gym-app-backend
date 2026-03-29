<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatusEnum;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with(['member.user', 'plan'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->member_id, fn ($q) => $q->where('member_id', $request->member_id))
            ->when($request->plan_id, fn ($q) => $q->where('plan_id', $request->plan_id))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(
            SubscriptionResource::collection($subscriptions),
            'Subscriptions retrieved successfully.'
        );
    }

    public function store(SubscriptionRequest $request): JsonResponse
    {
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

        return $this->success(
            SubscriptionResource::make($subscription->load(['member.user', 'plan'])),
            'Subscription created successfully.',
            201
        );
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return $this->success(
            SubscriptionResource::make($subscription->load(['member.user', 'plan', 'payments'])),
            'Subscription retrieved successfully.'
        );
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $request->validate([
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
        ]);

        $subscription->update($request->only(['auto_renew', 'notes', 'status']));

        return $this->success(
            SubscriptionResource::make($subscription->fresh()->load(['member.user', 'plan'])),
            'Subscription updated successfully.'
        );
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $subscription->update(['status' => SubscriptionStatusEnum::Cancelled]);
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
                422
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
            201
        );
    }
}
