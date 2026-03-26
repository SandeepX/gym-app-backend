<?php

namespace App\Http\Requests;

use App\Enums\WorkOutDifficultyLevelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class WorkoutPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'difficulty' => ['required', new Enum(WorkOutDifficultyLevelEnum::class)],
            'duration_weeks' => ['required', 'integer', 'min:1'],
            'days_per_week' => ['required', 'integer', 'min:1', 'max:7'],

            'goals' => ['nullable', 'array'],

            'exercises' => ['nullable', 'array'],

            'exercises.*.name' => ['required_with:exercises', 'string'],
            'exercises.*.muscle_group' => ['required_with:exercises', 'string'],
            'exercises.*.sets' => ['required_with:exercises', 'integer', 'min:1'],
            'exercises.*.reps' => ['required_with:exercises', 'integer', 'min:1'],
            'exercises.*.rest_seconds' => ['nullable', 'integer'],
            'exercises.*.instructions' => ['nullable', 'string'],
            'exercises.*.day_number' => ['required_with:exercises', 'integer', 'min:1'],
            'exercises.*.order' => ['nullable', 'integer'],
        ];
    }
}
