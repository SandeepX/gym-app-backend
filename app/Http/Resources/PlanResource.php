<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_days' => $this->duration_days,
            'max_freeze_days' => $this->max_freeze_days,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'features' => $this->features ?? [],
            'is_active' => $this->is_active,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
        ];
    }
}
