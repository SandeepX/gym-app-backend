<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutExerciseResource extends JsonResource
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
            'muscle_group' => $this->muscle_group,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'rest_seconds' => $this->rest_seconds,
            'instructions' => $this->instructions,
            'day_number' => $this->day_number,
            'order' => $this->order,
        ];
    }
}
