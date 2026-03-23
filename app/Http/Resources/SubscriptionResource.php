<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_number' => $this->subscription_number,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->user->name,
                'membership_number' => $this->member->membership_number,
            ]),
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'price' => $this->plan->price,
                'duration_days' => $this->plan->duration_days,
            ]),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'days_remaining' => $this->daysRemaining(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'freeze_start' => $this->freeze_start,
            'freeze_end' => $this->freeze_end,
            'freeze_days_used' => $this->freeze_days_used,
            'auto_renew' => $this->auto_renew,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
