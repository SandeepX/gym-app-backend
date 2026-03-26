<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BodyMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'muscle_mass' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'chest' => ['nullable', 'numeric', 'min:0'],
            'waist' => ['nullable', 'numeric', 'min:0'],
            'hips' => ['nullable', 'numeric', 'min:0'],
            'biceps' => ['nullable', 'numeric', 'min:0'],
            'thighs' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['nullable', 'date'],
        ];
    }
}
