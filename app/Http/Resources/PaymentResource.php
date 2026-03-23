<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->user->name,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription?->id,
                'plan' => $this->subscription?->plan?->name,
            ]),
            'collected_by' => $this->whenLoaded('collectedBy', fn () => $this->collectedBy?->name),
            'amount' => $this->amount,
            'payment_method' => $this->payment_method->value,
            'method_label' => $this->payment_method->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reference_number' => $this->reference_number,
            'paid_at' => $this->paid_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
