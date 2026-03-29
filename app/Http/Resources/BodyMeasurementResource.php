<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BodyMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'measured_at' => $this->measured_at?->format('Y-m-d'),
            'weight' => $this->weight,
            'height' => $this->height,
            'bmi_category' => $this->bmiCategory(),
            'bmi' => $this->bmi,
            'body_fat_percentage' => $this->body_fat_percentage,
            'muscle_mass' => $this->muscle_mass,
            'measurements' => [
                'chest' => $this->chest,
                'waist' => $this->waist,
                'hips' => $this->hips,
                'biceps' => $this->biceps,
                'thighs' => $this->thighs,
            ],
            'notes' => $this->notes,
            'recorded_by' => $this->whenLoaded('recordedBy', fn () => [
                'id' => $this->recordedBy?->id,
                'name' => $this->recordedBy?->name,
            ]),
        ];
    }
}
