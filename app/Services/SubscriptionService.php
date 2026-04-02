<?php

namespace App\Services;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class SubscriptionService
{
    public function getSubscriptionById($userId, $with = [])
    {
        $user = Subscription::with($with)->find($userId);

        if (! $user) {
            throw new RuntimeException('Subscription Detail Not Found', Response::HTTP_NOT_FOUND);
        }

        return $user;
    }

    public function store(Request $request)
    {
        $plan = Plan::findOrFail($request->plan_id);
        $endDate = Carbon::parse($request->start_date)->addDays($plan->duration_days);

        $insertData = [
            'subscription_number' => Subscription::generateSequenceNumber('SUB', 'subscription_number'),
            'member_id' => $request->member_id,
            'plan_id' => $request->plan_id,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'status' => SubscriptionStatusEnum::Active,
            'auto_renew' => $request->boolean('auto_renew'),
        ];

        return Subscription::create($insertData);
    }
}
