<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,

            'assigned_members_count' => $this->assigned_members_count
                ?? $this->whenLoaded('assignedMembers', fn () => $this->assignedMembers->count(), 0),

            'assigned_members' => $this->whenLoaded('assignedMembers', fn () => $this->assignedMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->user?->name,
                'membership_number' => $m->membership_number,
                'status' => $m->status->value,
            ])
            ),
        ];
    }
}
