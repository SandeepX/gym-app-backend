<?php

namespace App\Http\Requests;

use App\Enums\PlanTypeEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PlanRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'max_freeze_days' => ['nullable', 'integer', 'min:0'],
            'type' => ['required', new Enum(PlanTypeEnum::class)],
            'features' => ['nullable', 'array'],
            'features.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be at least 0.',
            'duration_days.required' => 'Duration is required.',
            'duration_days.min' => 'Duration must be at least 1 day.',
            'type.required' => 'Plan type is required.',
        ];
    }
}
