<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'membership_number' => $this->membership_number,
            'status' => $this->status,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth?->age,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'address' => $this->address,
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'health_notes' => $this->health_notes,

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'avatar' => $this->user->avatar,
            ]),

            'active_subscription' => $this->whenLoaded('activeSubscription', fn () => $this->activeSubscription ? [
                'id' => $this->activeSubscription->id,
                'subscription_number' => $this->activeSubscription->subscription_number,
                'plan' => [
                    'id' => $this->activeSubscription->plan?->id,
                    'name' => $this->activeSubscription->plan?->name,
                    'price' => $this->activeSubscription->plan?->price,
                    'duration_days' => $this->activeSubscription->plan?->duration_days,
                ],
                'start_date' => $this->activeSubscription->start_date?->format('Y-m-d'),
                'end_date' => $this->activeSubscription->end_date?->format('Y-m-d'),
                'days_remaining' => $this->activeSubscription->daysRemaining(),
                'status' => $this->activeSubscription->status->value,
            ] : null
            ),

            'subscriptions' => $this->whenLoaded('subscriptions', fn () => $this->subscriptions->map(fn ($sub) => [
                'id' => $sub->id,
                'subscription_number' => $sub->subscription_number,
                'plan' => $sub->plan?->name,
                'price' => $sub->plan?->price,
                'start_date' => $sub->start_date?->format('Y-m-d'),
                'end_date' => $sub->end_date?->format('Y-m-d'),
                'days_remaining' => $sub->daysRemaining(),
                'status' => $sub->status->value,
                'status_label' => $sub->status->label(),
            ])
            ),

            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => $payment->amount,
                'method' => $payment->payment_method->value,
                'method_label' => $payment->payment_method->label(),
                'status' => $payment->status->value,
                'plan' => $payment->subscription?->plan?->name,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i'),
            ])
            ),

            'trainers' => $this->whenLoaded('trainers', fn () => $this->trainers->map(fn ($trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
            ])
            ),

            'attendances' => $this->whenLoaded('attendances', fn () => $this->attendances->map(fn ($att) => [
                'id' => $att->id,
                'check_in' => $att->check_in?->format('Y-m-d H:i'),
                'check_out' => $att->check_out?->format('Y-m-d H:i'),
                'duration_minutes' => $att->durationMinutes(),
            ])
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
