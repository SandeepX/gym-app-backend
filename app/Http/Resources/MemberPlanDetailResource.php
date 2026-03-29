<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberPlanDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'member_id' => $this->id,
            'membership_number' => $this->membership_number,
            'workoutPlan' => WorkoutResource::collection(
                $this->whenLoaded('workoutPlans')
            ),
        ];
    }
}
