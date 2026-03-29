<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
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
            'difficulty' => $this->difficulty,
            'duration_weeks' => $this->duration_weeks,
            'days_per_week' => $this->days_per_week,
            'goals' => $this->goals,

            'exercises' => WorkoutExerciseResource::collection(
                $this->whenLoaded('exercises')
            ),
        ];
    }
}
